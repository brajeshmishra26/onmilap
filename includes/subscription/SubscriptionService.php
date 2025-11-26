<?php

namespace App\Subscription;

use DateInterval;
use DateTimeImmutable;
use DateTimeZone;
use Exception;
use Medoo\Medoo;
use Registry;

class SubscriptionService
{
    private const TRIAL_PLAN_SLUG = 'test';

    public function __construct(
        private Medoo $db,
        private PlanModel $planModel,
        private SubscriptionModel $subscriptionModel,
        private ?ExchangeRateService $exchangeRateService = null
    ) {
    }

    public function listPlans(?int $userId = null): array
    {
        $plans = $this->planModel->allActive();

        if ($userId === null || $userId <= 0 || empty($plans)) {
            return $plans;
        }

        if (!$this->subscriptionModel->userHasAnySubscription($userId)) {
            return $plans;
        }

        return array_values(array_filter($plans, function (array $plan): bool {
            return !$this->isTrialPlan($plan);
        }));
    }

    public function getPlanBySlug(string $slug): ?array
    {
        return $this->planModel->findBySlug($slug);
    }

    public function getPlanPrice(array $plan, string $currency): float
    {
        $currency = strtoupper($currency);
        if ($currency === 'INR') {
            if (array_key_exists('price_inr', $plan) && $plan['price_inr'] !== null && $plan['price_inr'] !== '') {
                return (float) $plan['price_inr'];
            }
            if ($this->exchangeRateService !== null && array_key_exists('price_usd', $plan) && $plan['price_usd'] !== null) {
                $rate = $this->exchangeRateService->getUsdToInrRate();
                return (float) $plan['price_usd'] * $rate;
            }
        } else {
            if (array_key_exists('price_usd', $plan) && $plan['price_usd'] !== null && $plan['price_usd'] !== '') {
                return (float) $plan['price_usd'];
            }
            if ($this->exchangeRateService !== null && array_key_exists('price_inr', $plan) && $plan['price_inr'] !== null) {
                $rate = $this->exchangeRateService->getUsdToInrRate();
                if ($rate > 0) {
                    return (float) $plan['price_inr'] / $rate;
                }
            }
        }

        throw new Exception('Pricing for the selected currency is unavailable.');
    }

    public function purchasePlan(int $userId, string $planSlug, string $currency, array $options = []): array
    {
        $plan = $this->planModel->findBySlug($planSlug);

        if (!$plan) {
            throw new Exception('Selected plan is unavailable.');
        }

        if ($this->isTrialPlan($plan) && $this->subscriptionModel->userHasAnySubscription($userId)) {
            throw new Exception('Trial plan is available only for new users.');
        }

        $currency = strtoupper($currency);
        $gateway = $currency === 'INR' ? 'razorpay' : 'paypal';

        $pdo = $this->db->pdo;
        $pdo->beginTransaction();

        try {
            $subscription = $this->subscriptionModel->findActiveForUser($userId, true);
            $nowUtc = new DateTimeImmutable('now', new \DateTimeZone('UTC'));

            if ((int) $plan['is_top_up'] === 1) {
                $result = $this->applyTopUp($userId, $plan, $subscription, $currency, $gateway, $options, $nowUtc);
            } else {
                $result = $this->replaceSubscription($userId, $plan, $currency, $gateway, $options, $nowUtc);
            }

            $pdo->commit();

            $subscriptionDetails = $this->getActiveSubscriptionDetails($userId);
            $this->queuePurchaseConfirmationEmail(
                $userId,
                $plan,
                $currency,
                $gateway,
                $options['payment_reference'] ?? null,
                $subscriptionDetails
            );

            return $result;
        } catch (Exception $exception) {
            $pdo->rollBack();
            throw $exception;
        }
    }

    public function deductMinutesOnSessionEnd(int $userId, float $sessionMinutes, array $metadata = []): array
    {
        if ($sessionMinutes <= 0) {
            throw new Exception('Invalid session length.');
        }

        // Round up to make sure sub-minute sessions still deduct at least one minute
        $minutes = (int) ceil($sessionMinutes);

        $pdo = $this->db->pdo;
        $pdo->beginTransaction();

        try {
            $subscription = $this->subscriptionModel->findActiveForUser($userId, true);
            if ($subscription && !empty($subscription['end_at'])) {
                $endAt = new DateTimeImmutable($subscription['end_at'], new DateTimeZone('UTC'));
                $nowUtc = new DateTimeImmutable('now', new DateTimeZone('UTC'));
                if ($endAt < $nowUtc) {
                    $this->subscriptionModel->update((int) $subscription['id'], ['status' => 'expired']);
                    $subscription = null;
                }
            }

            if (!$subscription) {
                throw new Exception('No active subscription found.');
            }

            if ((int) $subscription['daily_remaining_minutes'] < $minutes) {
                throw new Exception('Daily minutes exhausted.');
            }
            if ((int) $subscription['total_remaining_minutes'] < $minutes) {
                throw new Exception('Total minutes exhausted.');
            }

            $newDaily = (int) $subscription['daily_remaining_minutes'] - $minutes;
            $newTotal = (int) $subscription['total_remaining_minutes'] - $minutes;

            $this->subscriptionModel->update((int) $subscription['id'], [
                'daily_remaining_minutes' => $newDaily,
                'total_remaining_minutes' => $newTotal,
                'last_activity_at' => gmdate('Y-m-d H:i:s'),
            ]);

            $this->subscriptionModel->logEvent((int) $subscription['id'], $userId, 'minutes_deducted', [
                'session_minutes' => $minutes,
                'metadata' => $metadata,
            ]);

            $pdo->commit();

            $this->queueSessionSummaryEmail($subscription, $newDaily, $newTotal);

            return [
                'daily_remaining_minutes' => $newDaily,
                'total_remaining_minutes' => $newTotal,
            ];
        } catch (Exception $exception) {
            $pdo->rollBack();
            throw $exception;
        }
    }

    public function computeRemainingDays(DateTimeImmutable $endAt): int
    {
        $now = new DateTimeImmutable('now', new \DateTimeZone('UTC'));
        $diff = $endAt->getTimestamp() - $now->getTimestamp();
        return max(0, (int) ceil($diff / 86400));
    }

    public function getUsdToInr(): ?float
    {
        if ($this->exchangeRateService === null) {
            return null;
        }

        return $this->exchangeRateService->getUsdToInrRate();
    }

    public function getActiveSubscriptionDetails(int $userId): ?array
    {
        $subscription = $this->subscriptionModel->findActiveWithPlan($userId);
        if (!$subscription) {
            return null;
        }

        $nowUtc = new DateTimeImmutable('now', new DateTimeZone('UTC'));
        if (!empty($subscription['end_at'])) {
            $endAtCandidate = new DateTimeImmutable($subscription['end_at'], new DateTimeZone('UTC'));
            if ($endAtCandidate < $nowUtc) {
                $this->subscriptionModel->update((int) $subscription['id'], ['status' => 'expired']);
                return null;
            }
        }

        $timezone = $this->resolveUserTimezone($userId);
        $endAtUtc = !empty($subscription['end_at']) ? new DateTimeImmutable($subscription['end_at'], new DateTimeZone('UTC')) : null;
        $startAtUtc = !empty($subscription['start_at']) ? new DateTimeImmutable($subscription['start_at'], new DateTimeZone('UTC')) : null;
        $remainingDays = $endAtUtc ? $this->computeRemainingDays($endAtUtc) : null;

        return [
            'id' => (int) $subscription['id'],
            'plan_name' => $subscription['plan_name'] ?? 'Current Plan',
            'plan_slug' => $subscription['plan_slug'] ?? null,
            'status' => $subscription['status'] ?? 'inactive',
            'currency' => $subscription['currency'] ?? 'INR',
            'payment_reference' => $subscription['payment_reference'] ?? null,
            'total_allocated_minutes' => (int) ($subscription['total_allocated_minutes'] ?? 0),
            'total_remaining_minutes' => (int) ($subscription['total_remaining_minutes'] ?? 0),
            'daily_minutes_limit' => (int) ($subscription['daily_minutes_limit'] ?? 0),
            'daily_remaining_minutes' => (int) ($subscription['daily_remaining_minutes'] ?? 0),
            'start_at_utc' => $subscription['start_at'] ?? null,
            'end_at_utc' => $subscription['end_at'] ?? null,
            'last_activity_utc' => $subscription['last_activity_at'] ?? null,
            'start_at_display' => $this->formatDateForUser($subscription['start_at'] ?? null, $timezone),
            'end_at_display' => $this->formatDateForUser($subscription['end_at'] ?? null, $timezone),
            'last_activity_display' => $this->formatDateForUser($subscription['last_activity_at'] ?? null, $timezone),
            'remaining_days' => $remainingDays,
            'plan_price_inr' => $subscription['plan_price_inr'] ?? null,
            'plan_price_usd' => $subscription['plan_price_usd'] ?? null,
        ];
    }

    public function listRecentSubscriptions(int $userId, int $limit = 5): array
    {
        $timezone = $this->resolveUserTimezone($userId);
        $rows = $this->subscriptionModel->latestForUser($userId, $limit);

        if (empty($rows)) {
            return [];
        }

        return array_map(function (array $row) use ($timezone) {
            $amount = null;
            if (($row['currency'] ?? '') === 'INR') {
                $amount = isset($row['plan_price_inr']) ? (float) $row['plan_price_inr'] : null;
            } else {
                $amount = isset($row['plan_price_usd']) ? (float) $row['plan_price_usd'] : null;
            }

            return [
                'id' => (int) $row['id'],
                'plan_name' => $row['plan_name'] ?? ('Plan #' . ($row['plan_id'] ?? '')),
                'plan_slug' => $row['plan_slug'] ?? null,
                'status' => $row['status'] ?? 'inactive',
                'currency' => $row['currency'] ?? 'INR',
                'amount' => $amount,
                'payment_reference' => $row['payment_reference'] ?? null,
                'purchased_on' => $this->formatDateForUser($row['start_at'] ?? null, $timezone),
                'valid_till' => $this->formatDateForUser($row['end_at'] ?? null, $timezone),
                'total_minutes' => isset($row['total_allocated_minutes']) ? (int) $row['total_allocated_minutes'] : null,
            ];
        }, $rows);
    }

    private function replaceSubscription(int $userId, array $plan, string $currency, string $gateway, array $options, DateTimeImmutable $now): array
    {
        $startAt = $now;
        $endAt = $now->add(new DateInterval('P' . (int) $plan['validity_days'] . 'D'));

        $subscriptionId = $this->subscriptionModel->create([
            'user_id' => $userId,
            'plan_id' => $plan['id'],
            'status' => 'active',
            'start_at' => $startAt->format('Y-m-d H:i:s'),
            'end_at' => $endAt->format('Y-m-d H:i:s'),
            'timezone' => $options['timezone'] ?? 'UTC',
            'total_allocated_minutes' => $plan['total_minutes'],
            'total_remaining_minutes' => $plan['total_minutes'],
            'daily_minutes_limit' => $plan['daily_minutes_limit'],
            'daily_remaining_minutes' => $plan['daily_minutes_limit'],
            'last_reset_at' => $now->format('Y-m-d H:i:s'),
            'currency' => $currency,
            'payment_reference' => $options['payment_reference'] ?? null,
            'next_recharge_at' => $endAt->format('Y-m-d H:i:s'),
        ]);

        $this->subscriptionModel->logEvent($subscriptionId, $userId, 'plan_purchased', [
            'plan_id' => $plan['id'],
            'currency' => $currency,
            'gateway' => $gateway,
        ]);

        return [
            'subscription_id' => $subscriptionId,
            'start_at' => $startAt->format(DATE_ATOM),
            'end_at' => $endAt->format(DATE_ATOM),
        ];
    }

    private function applyTopUp(int $userId, array $plan, ?array $subscription, string $currency, string $gateway, array $options, DateTimeImmutable $now): array
    {
        if (!$subscription) {
            return $this->replaceSubscription($userId, $plan, $currency, $gateway, $options, $now);
        }

        $newTotal = (int) $subscription['total_remaining_minutes'] + (int) $plan['total_minutes'];
        $newDaily = min(
            (int) $subscription['daily_minutes_limit'],
            (int) $subscription['daily_remaining_minutes'] + (int) $plan['daily_minutes_limit']
        );

        $endAt = new DateTimeImmutable($subscription['end_at'], new \DateTimeZone('UTC'));
        if ((int) $plan['extends_validity_days'] > 0) {
            $endAt = $endAt->add(new DateInterval('P' . (int) $plan['extends_validity_days'] . 'D'));
        }

        $this->subscriptionModel->update((int) $subscription['id'], [
            'total_remaining_minutes' => $newTotal,
            'daily_remaining_minutes' => $newDaily,
            'end_at' => $endAt->format('Y-m-d H:i:s'),
        ]);

        $this->subscriptionModel->insertTopUp([
            'subscription_id' => $subscription['id'],
            'user_id' => $userId,
            'plan_id' => $plan['id'],
            'minutes_purchased' => $plan['total_minutes'],
            'validity_days' => $plan['validity_days'],
            'extends_validity_days' => $plan['extends_validity_days'],
            'amount' => $currency === 'INR' ? $plan['price_inr'] : $plan['price_usd'],
            'currency' => $currency,
            'payment_gateway' => $gateway,
            'payment_reference' => $options['payment_reference'] ?? uniqid('topup_', true),
            'status' => 'captured',
            'metadata' => json_encode($options['metadata'] ?? []),
        ]);

        $this->subscriptionModel->logEvent((int) $subscription['id'], $userId, 'topup_applied', [
            'minutes_added' => $plan['total_minutes'],
            'gateway' => $gateway,
        ]);

        return [
            'subscription_id' => (int) $subscription['id'],
            'end_at' => $endAt->format(DATE_ATOM),
            'total_remaining_minutes' => $newTotal,
            'daily_remaining_minutes' => $newDaily,
        ];
    }

    private function queueSessionSummaryEmail(array $subscription, int $dailyRemaining, int $totalRemaining): void
    {
        $user = $this->db->get('site_users', ['email_address(email)', 'display_name'], ['user_id' => $subscription['user_id']]);
        if (!$user || empty($user['email'])) {
            return;
        }

        $endAt = new DateTimeImmutable($subscription['end_at'], new \DateTimeZone('UTC'));
        $publicSiteUrl = $this->getPublicSiteUrl();
        $subscriptionUrl = $publicSiteUrl !== ''
            ? $publicSiteUrl . 'subscription.php'
            : (Registry::load('config')->site_url ?? '') . 'subscription.php';
        $content = $this->renderEmailTemplate('session_summary', [
            'displayName' => $user['display_name'] ?? 'there',
            'dailyRemaining' => $dailyRemaining,
            'totalRemaining' => $totalRemaining,
            'expiryDate' => $endAt->format('F j, Y'),
            'ctaUrl' => $subscriptionUrl,
            'supportEmail' => Registry::load('settings')->system_email_address ?? 'support@example.com',
        ]);

        $payload = [
            'send_to' => $user['email'],
            'subject' => 'Session usage summary',
            'heading' => 'Usage summary',
            'content' => $content,
            'button' => [
                'label' => 'Recharge now',
                'link' => $subscriptionUrl,
            ],
        ];

        mailer('send', $payload);
    }

    private function queuePurchaseConfirmationEmail(
        int $userId,
        array $plan,
        string $currency,
        string $gateway,
        ?string $paymentReference,
        ?array $subscriptionDetails
    ): void {
        $user = $this->db->get('site_users', ['email_address(email)', 'display_name', 'time_zone'], ['user_id' => $userId]);
        if (!$user || empty($user['email'])) {
            return;
        }

        $amount = null;
        if ($currency === 'INR' && isset($plan['price_inr'])) {
            $amount = (float) $plan['price_inr'];
        } elseif ($currency !== 'INR' && isset($plan['price_usd'])) {
            $amount = (float) $plan['price_usd'];
        }

        $timezone = $user['time_zone'] ?? 'UTC';
        $expiryDisplay = null;
        if ($subscriptionDetails !== null) {
            $expiryDisplay = $subscriptionDetails['end_at_display']
                ?? $this->formatDateForUser($subscriptionDetails['end_at_utc'] ?? null, $timezone, 'F j, Y');
        }

        $minutes = $subscriptionDetails['total_allocated_minutes'] ?? ($plan['total_minutes'] ?? null);
        $publicSiteUrl = $this->getPublicSiteUrl();
        $subscriptionUrl = $publicSiteUrl !== ''
            ? $publicSiteUrl . 'subscription.php'
            : (Registry::load('config')->site_url ?? '') . 'subscription.php';

        $content = $this->renderEmailTemplate('purchase_confirmation', [
            'displayName' => $user['display_name'] ?? 'there',
            'planName' => $plan['name'] ?? 'Subscription plan',
            'amount' => $amount !== null ? number_format($amount, 2) : null,
            'currency' => $currency,
            'paymentReference' => $paymentReference,
            'validTill' => $expiryDisplay,
            'minutes' => $minutes,
            'gateway' => ucfirst($gateway),
            'ctaUrl' => $subscriptionUrl,
            'supportEmail' => Registry::load('settings')->system_email_address ?? 'support@example.com',
        ]);

        $payload = [
            'send_to' => $user['email'],
            'subject' => 'Your onMilap recharge is confirmed',
            'heading' => 'Recharge confirmed',
            'content' => $content,
            'button' => [
                'label' => 'View plans',
                'link' => $subscriptionUrl,
            ],
        ];

        mailer('send', $payload);
    }

    private function resolveUserTimezone(int $userId): string
    {
        $default = 'UTC';

        if (class_exists('Registry')) {
            try {
                $currentUser = Registry::load('current_user');
            } catch (\Throwable $exception) {
                $currentUser = null;
            }

            if ($currentUser && isset($currentUser->id) && (int) $currentUser->id === $userId) {
                return $currentUser->time_zone ?? $default;
            }
        }

        $record = $this->db->get('site_users', ['time_zone'], ['user_id' => $userId]);
        if ($record && !empty($record['time_zone'])) {
            return $record['time_zone'];
        }

        return $default;
    }

    private function formatDateForUser(?string $value, string $timezone, string $format = 'M j, Y g:i A'): ?string
    {
        if (empty($value)) {
            return null;
        }

        try {
            $date = new DateTimeImmutable($value, new DateTimeZone('UTC'));
            $tz = new DateTimeZone($timezone ?: 'UTC');
            return $date->setTimezone($tz)->format($format);
        } catch (\Throwable $exception) {
            return $value;
        }
    }

    private function getPublicSiteUrl(): string
    {
        $config = Registry::load('config');
        $preferred = isset($config->public_site_url) ? trim((string) $config->public_site_url) : '';

        if ($preferred !== '') {
            return rtrim($preferred, '/') . '/';
        }

        $siteUrl = $config->site_url ?? '';
        $siteUrl = trim((string) $siteUrl);
        if ($siteUrl === '') {
            return '';
        }

        $normalized = rtrim($siteUrl, '/');
        $publicBase = preg_replace('#/chat/?$#i', '', $normalized);
        if ($publicBase === '') {
            $publicBase = $normalized;
        }

        return $publicBase !== '' ? $publicBase . '/' : '';
    }

    private function renderEmailTemplate(string $view, array $data): string
    {
        $viewFile = __DIR__ . '/../..' . '/views/emails/' . $view . '.php';
        if (!is_file($viewFile)) {
            throw new Exception('Email view missing: ' . $view);
        }

        extract($data, EXTR_SKIP);
        ob_start();
        include $viewFile;
        return trim((string) ob_get_clean());
    }

    private function isTrialPlan(array $plan): bool
    {
        if (!array_key_exists('slug', $plan)) {
            return false;
        }

        return strtolower((string) $plan['slug']) === self::TRIAL_PLAN_SLUG;
    }
}
