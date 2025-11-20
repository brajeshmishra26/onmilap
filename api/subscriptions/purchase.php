<?php

declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

try {
    $body = json_decode(file_get_contents('php://input'), true) ?: $_POST;
    $userId = isset($body['user_id']) ? (int) $body['user_id'] : 0;
    $planSlug = $body['plan_slug'] ?? '';
    $currency = strtoupper($body['currency'] ?? 'USD');

    if ($userId <= 0 || empty($planSlug)) {
        throw new InvalidArgumentException('user_id and plan_slug are required.');
    }

    $service = subscription_service();
    $plan = $service->getPlanBySlug($planSlug);
    if (!$plan) {
        throw new InvalidArgumentException('Selected plan is unavailable.');
    }

    $amount = $service->getPlanPrice($plan, $currency);

    if ($amount <= 0) {
        $result = $service->purchasePlan($userId, $planSlug, $currency, [
            'timezone' => $body['timezone'] ?? 'UTC',
            'payment_reference' => 'free_plan',
            'metadata' => $body['metadata'] ?? [],
        ]);

        echo json_encode([
            'ok' => true,
            'status' => 'completed',
            'data' => $result,
        ]);
        return;
    }

    if ($currency === 'INR') {
        $credentials = subscription_resolve_razorpay_credentials();
        if (!$credentials) {
            throw new Exception('Razorpay gateway is not configured.');
        }

        $order = subscription_create_razorpay_order($userId, $plan, $amount, $credentials);

        echo json_encode([
            'ok' => true,
            'status' => 'pending',
            'gateway' => 'razorpay',
            'order' => $order,
        ]);
        return;
    }

    if ($currency === 'USD') {
        $credentials = subscription_resolve_paypal_credentials();
        if (!$credentials) {
            throw new Exception('PayPal gateway is not configured.');
        }

        $siteUrl = Registry::load('config')->site_url ?? '';
        $publicBase = preg_replace('#/chat/?$#', '', rtrim($siteUrl, '/'));
        if ($publicBase === '') {
            $publicBase = rtrim($siteUrl, '/');
        }
        if ($publicBase === '') {
            throw new Exception('Site URL is not configured properly.');
        }
        $publicBase = rtrim($publicBase, '/') . '/';

        $returnUrl = $publicBase . 'api/subscriptions/paypal_complete.php?action=success';
        $cancelUrl = $publicBase . 'api/subscriptions/paypal_complete.php?action=cancel';

        $session = subscription_create_paypal_checkout($userId, $plan, $amount, $credentials, $returnUrl, $cancelUrl);

        if (!isset($_SESSION['subscription_paypal'])) {
            $_SESSION['subscription_paypal'] = [];
        }
        if (!empty($session['payment_id'])) {
            $_SESSION['subscription_paypal'][$session['payment_id']] = [
                'user_id' => $userId,
                'plan_slug' => $planSlug,
                'currency' => $currency,
                'amount' => $amount,
                'created_at' => time(),
            ];
        }

        echo json_encode([
            'ok' => true,
            'status' => 'redirect',
            'gateway' => 'paypal',
            'checkout_url' => $session['checkout_url'],
        ]);
        return;
    }

    throw new Exception('Unsupported currency selected.');
} catch (Throwable $exception) {
    http_response_code(422);
    echo json_encode([
        'ok' => false,
        'error' => $exception->getMessage(),
    ]);
}
