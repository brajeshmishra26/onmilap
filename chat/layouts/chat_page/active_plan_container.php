<?php
require_once dirname(__DIR__, 3).'/includes/subscription/bootstrap.php';

$service = subscription_service();
$currentUserId = Registry::load('current_user')->id ?? 0;
$activePlan = $currentUserId ? $service->getActiveSubscriptionDetails((int)$currentUserId) : null;
$recentPurchases = $currentUserId ? $service->listRecentSubscriptions((int)$currentUserId, 1) : [];
$lastRecharge = $recentPurchases[0] ?? null;

$chatBaseUrl = rtrim(Registry::load('config')->site_url ?? '', '/');
$publicBaseUrl = preg_replace('#/chat/?$#i', '', $chatBaseUrl);
if ($publicBaseUrl === '') {
    $publicBaseUrl = $chatBaseUrl;
}
if ($publicBaseUrl !== '' && substr($publicBaseUrl, -1) !== '/') {
    $publicBaseUrl .= '/';
}
$subscriptionPageUrl = $publicBaseUrl.'subscription.php';
$historyPageUrl = $subscriptionPageUrl.'?view=history';
$backToChatUrl = $chatBaseUrl !== '' ? $chatBaseUrl.'/' : './';

include __DIR__.'/subscription_styles.php';

$currencyCode = $activePlan['currency'] ?? 'INR';
$amountLabel = null;
if ($activePlan) {
    if ($currencyCode === 'INR' && isset($activePlan['plan_price_inr'])) {
        $amountLabel = '₹'.number_format((float)$activePlan['plan_price_inr'], 2);
    } elseif (isset($activePlan['plan_price_usd'])) {
        $amountLabel = '$'.number_format((float)$activePlan['plan_price_usd'], 2);
    }
}
$statusLabel = $activePlan ? ucfirst((string)($activePlan['status'] ?? 'inactive')) : 'Inactive';
$lastRechargeOn = $lastRecharge['purchased_on'] ?? null;
$lastRechargeAmount = $lastRecharge && isset($lastRecharge['amount']) && $lastRecharge['amount'] !== null
    ? number_format((float)$lastRecharge['amount'], 2)
    : null;
?>
<div class="subscription-page">
    <section class="subscription-hero">
        <div>
            <p class="eyebrow">Active plan</p>
            <h1>
                <?php if ($activePlan): ?>
                    <?= htmlspecialchars($activePlan['plan_name'] ?? 'Current Plan', ENT_QUOTES, 'UTF-8'); ?>
                <?php else: ?>
                    No active plan
                <?php endif; ?>
            </h1>
            <p class="subtitle">
                <?php if ($activePlan): ?>
                    <?= htmlspecialchars($statusLabel, ENT_QUOTES, 'UTF-8'); ?> ·
                    <?= number_format((int)($activePlan['total_remaining_minutes'] ?? 0)); ?> minutes left
                    <?php if (!empty($activePlan['remaining_days'])): ?>
                        · <?= (int)$activePlan['remaining_days']; ?> day(s) remaining
                    <?php endif; ?>
                <?php else: ?>
                    You currently do not have an active subscription. Recharge to unlock minutes instantly.
                <?php endif; ?>
            </p>
        </div>
        <div>
            <a class="back-to-chat" href="<?= htmlspecialchars($backToChatUrl, ENT_QUOTES, 'UTF-8'); ?>">
                ← Back to chat
            </a>
            <a class="secondary-btn" href="<?= htmlspecialchars($historyPageUrl, ENT_QUOTES, 'UTF-8'); ?>">
                View history
            </a>
            <a class="back-to-chat" href="<?= htmlspecialchars($subscriptionPageUrl, ENT_QUOTES, 'UTF-8'); ?>">
                Recharge now
            </a>
        </div>
    </section>

    <?php if ($activePlan): ?>
        <div class="plan-overview-grid">
            <div class="plan-stat-card">
                <div class="card-body">
                    <p class="stat-label">Total minutes</p>
                    <p class="stat-value"><?= number_format((int)($activePlan['total_allocated_minutes'] ?? 0)); ?></p>
                    <p class="stat-label">Remaining: <?= number_format((int)($activePlan['total_remaining_minutes'] ?? 0)); ?></p>
                </div>
            </div>
            <div class="plan-stat-card">
                <div class="card-body">
                    <p class="stat-label">Daily limit</p>
                    <p class="stat-value"><?= number_format((int)($activePlan['daily_minutes_limit'] ?? 0)); ?></p>
                    <p class="stat-label">Today left: <?= number_format((int)($activePlan['daily_remaining_minutes'] ?? 0)); ?></p>
                </div>
            </div>
            <div class="plan-stat-card">
                <div class="card-body">
                    <p class="stat-label">Plan amount</p>
                    <p class="stat-value">
                        <?= $amountLabel !== null ? htmlspecialchars($amountLabel, ENT_QUOTES, 'UTF-8') : '—'; ?>
                    </p>
                    <p class="stat-label">Currency: <?= htmlspecialchars($currencyCode, ENT_QUOTES, 'UTF-8'); ?></p>
                </div>
            </div>
            <div class="plan-stat-card">
                <div class="card-body">
                    <p class="stat-label">Last recharge</p>
                    <p class="stat-value">
                        <?= $lastRechargeOn ? htmlspecialchars($lastRechargeOn, ENT_QUOTES, 'UTF-8') : '—'; ?>
                    </p>
                    <p class="stat-label">
                        <?php if ($lastRechargeAmount !== null): ?>
                            <?= htmlspecialchars($currencyCode, ENT_QUOTES, 'UTF-8'); ?> <?= htmlspecialchars($lastRechargeAmount, ENT_QUOTES, 'UTF-8'); ?>
                        <?php else: ?>
                            Amount unavailable
                        <?php endif; ?>
                    </p>
                </div>
            </div>
        </div>

        <div class="plan-details-panel">
            <h2 style="margin-top:0;">Plan details</h2>
            <ul class="plan-meta-list">
                <li>
                    <span>Status</span>
                    <strong><?= htmlspecialchars($statusLabel, ENT_QUOTES, 'UTF-8'); ?></strong>
                </li>
                <li>
                    <span>Started</span>
                    <strong><?= htmlspecialchars($activePlan['start_at_display'] ?? '—', ENT_QUOTES, 'UTF-8'); ?></strong>
                </li>
                <li>
                    <span>Valid till</span>
                    <strong><?= htmlspecialchars($activePlan['end_at_display'] ?? '—', ENT_QUOTES, 'UTF-8'); ?></strong>
                </li>
                <li>
                    <span>Payment reference</span>
                    <strong><?= htmlspecialchars($activePlan['payment_reference'] ?? '—', ENT_QUOTES, 'UTF-8'); ?></strong>
                </li>
                <li>
                    <span>Last activity</span>
                    <strong><?= htmlspecialchars($activePlan['last_activity_display'] ?? '—', ENT_QUOTES, 'UTF-8'); ?></strong>
                </li>
                <li>
                    <span>Recharge link</span>
                    <strong>
                        <a href="<?= htmlspecialchars($subscriptionPageUrl, ENT_QUOTES, 'UTF-8'); ?>" style="color:#a5b4fc;">
                            subscription.php
                        </a>
                    </strong>
                </li>
            </ul>
        </div>
    <?php else: ?>
        <div class="history-panel">
            <div class="history-empty">
                <p>No active subscription found for your account.</p>
                <p>
                    <a class="back-to-chat" href="<?= htmlspecialchars($subscriptionPageUrl, ENT_QUOTES, 'UTF-8'); ?>">
                        Choose a plan
                    </a>
                </p>
            </div>
        </div>
    <?php endif; ?>
</div>
