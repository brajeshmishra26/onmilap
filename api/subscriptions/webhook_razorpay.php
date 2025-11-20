<?php

declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';

header('Content-Type: application/json');

$payload = file_get_contents('php://input');
$data = json_decode($payload, true);

$credentials = subscription_resolve_razorpay_credentials();
if (!$credentials) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Razorpay gateway unavailable']);
    exit;
}

$orderId = $data['razorpay_order_id'] ?? '';
$paymentId = $data['razorpay_payment_id'] ?? '';
$signature = $data['razorpay_signature'] ?? '';
$entity = null;

if (empty($orderId) || empty($paymentId)) {
    if (!isset($data['payload']['payment']['entity'])) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => 'Invalid payload']);
        exit;
    }
    $entity = $data['payload']['payment']['entity'];
    $orderId = $entity['order_id'] ?? '';
    $paymentId = $entity['id'] ?? '';
    $signature = $_SERVER['HTTP_X_RAZORPAY_SIGNATURE'] ?? '';
}

if (empty($orderId) || empty($paymentId) || empty($signature)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Missing Razorpay identifiers']);
    exit;
}

$expected = hash_hmac('sha256', $orderId . '|' . $paymentId, $credentials['key_secret']);
if (!hash_equals($expected, $signature)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Signature verification failed']);
    exit;
}

try {
    $api = new \Razorpay\Api\Api($credentials['key_id'], $credentials['key_secret']);
    if ($entity === null) {
        $entity = $api->payment->fetch($paymentId)->toArray();
    }
    $order = $api->order->fetch($orderId)->toArray();

    $notes = $order['notes'] ?? $entity['notes'] ?? [];
    $userId = isset($notes['user_id']) ? (int) $notes['user_id'] : 0;
    $planSlug = $notes['plan_slug'] ?? '';

    if ($userId <= 0 || empty($planSlug)) {
        http_response_code(422);
        echo json_encode(['ok' => false, 'error' => 'Metadata missing']);
        exit;
    }

    $service = subscription_service();
    $service->purchasePlan($userId, $planSlug, 'INR', [
        'payment_reference' => $paymentId,
        'metadata' => $entity,
    ]);

    echo json_encode(['ok' => true]);
} catch (Throwable $exception) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => $exception->getMessage()]);
}
