<?php

declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function subscription_redirect_to_portal(string $status, string $message = ''): void
{
    $siteUrl = Registry::load('config')->site_url ?? '';
    $publicBase = preg_replace('#/chat/?$#', '', rtrim($siteUrl, '/'));
    if ($publicBase === '') {
        $publicBase = rtrim($siteUrl, '/');
    }
    $publicBase = rtrim($publicBase, '/') . '/';
    $target = $publicBase . 'subscription.php';
    $query = http_build_query([
        'paypal_status' => $status,
        'message' => $message,
    ]);
    header('Location: ' . $target . (strpos($target, '?') === false ? '?' : '&') . $query);
    exit;
}

$action = $_GET['action'] ?? 'success';
$paymentId = $_GET['paymentId'] ?? $_GET['payment_id'] ?? '';
$payerId = $_GET['PayerID'] ?? $_GET['payerId'] ?? '';

if ($action === 'cancel') {
    subscription_redirect_to_portal('cancelled', 'You cancelled the PayPal checkout.');
}

if (empty($paymentId) || empty($payerId)) {
    subscription_redirect_to_portal('error', 'Missing PayPal confirmation data.');
}

$sessionData = $_SESSION['subscription_paypal'][$paymentId] ?? null;
if (!$sessionData) {
    subscription_redirect_to_portal('error', 'PayPal session expired.');
}

$credentials = subscription_resolve_paypal_credentials();
if (!$credentials) {
    subscription_redirect_to_portal('error', 'PayPal gateway unavailable.');
}

try {
    $gateway = \Omnipay\Omnipay::create('PayPal_Rest');
    $gateway->initialize([
        'clientId' => $credentials['client_id'],
        'secret' => $credentials['client_secret'],
        'testMode' => $credentials['test_mode'],
    ]);

    $response = $gateway->completePurchase([
        'payer_id' => $payerId,
        'transactionReference' => $paymentId,
    ])->send();

    if (!$response->isSuccessful()) {
        subscription_redirect_to_portal('error', $response->getMessage() ?: 'Unable to capture PayPal payment.');
    }

    $service = subscription_service();
    $service->purchasePlan(
        (int) $sessionData['user_id'],
        $sessionData['plan_slug'],
        $sessionData['currency'],
        [
            'payment_reference' => $paymentId,
            'metadata' => ['gateway' => 'paypal'],
        ]
    );

    unset($_SESSION['subscription_paypal'][$paymentId]);

    subscription_redirect_to_portal('success', 'Subscription recharged successfully.');
} catch (Throwable $exception) {
    subscription_redirect_to_portal('error', $exception->getMessage());
}
