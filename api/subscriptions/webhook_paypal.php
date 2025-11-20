<?php

declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';

$rawPost = file_get_contents('php://input');
if (empty($rawPost)) {
    http_response_code(400);
    exit('No payload');
}

$verification = 'cmd=_notify-validate&' . $rawPost;
$ch = curl_init('https://ipnpb.paypal.com/cgi-bin/webscr');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => $verification,
    CURLOPT_HTTPHEADER => ['Connection: close'],
    CURLOPT_TIMEOUT => 15,
]);
$response = curl_exec($ch);
curl_close($ch);

if ($response !== 'VERIFIED') {
    http_response_code(400);
    exit('IPN verification failed');
}

parse_str($rawPost, $data);
$userId = isset($data['custom_user_id']) ? (int) $data['custom_user_id'] : 0;
$planSlug = $data['item_number'] ?? '';
$txnId = $data['txn_id'] ?? '';

if ($userId <= 0 || empty($planSlug) || empty($txnId)) {
    http_response_code(422);
    exit('Missing data');
}

try {
    $service = subscription_service();
    $service->purchasePlan($userId, $planSlug, 'USD', [
        'payment_reference' => $txnId,
        'metadata' => $data,
    ]);
    http_response_code(200);
    echo 'ok';
} catch (Throwable $exception) {
    http_response_code(500);
    echo $exception->getMessage();
}
