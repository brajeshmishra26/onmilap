<?php
function getPesapalAccessToken($consumerKey, $consumerSecret, $env = 'sandbox') {
    $url = $env === 'live'
    ? 'https://pay.pesapal.com/v3/api/Auth/RequestToken'
    : 'https://cybqa.pesapal.com/pesapalv3/api/Auth/RequestToken';

    $payload = json_encode([
        "consumer_key" => $consumerKey,
        "consumer_secret" => $consumerSecret
    ]);

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_HTTPHEADER => ["Content-Type: application/json"],
        CURLOPT_POSTFIELDS => $payload,
        CURLOPT_RETURNTRANSFER => true
    ]);

    $response = json_decode(curl_exec($ch), true);
    curl_close($ch);

    if (!isset($response['token'])) {
        throw new Exception("Failed to get access token: " . json_encode($response));
    }

    return $response['token'];
}

function registerPesapalIPN($accessToken, $ipnUrl, $env = 'sandbox') {
    $url = $env === 'live'
    ? 'https://pay.pesapal.com/v3/api/URLSetup/RegisterIPN'
    : 'https://cybqa.pesapal.com/pesapalv3/api/URLSetup/RegisterIPN';

    $payload = json_encode([
        "url" => $ipnUrl,
        "ipn_notification_type" => "POST"
    ]);

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_HTTPHEADER => [
            "Authorization: Bearer $accessToken",
            "Content-Type: application/json",
            "Accept: application/json"
        ],
        CURLOPT_POSTFIELDS => $payload,
        CURLOPT_RETURNTRANSFER => true
    ]);

    $response = json_decode(curl_exec($ch), true);
    curl_close($ch);

    if (!isset($response['ipn_id'])) {
        throw new Exception("IPN registration failed: " . json_encode($response));
    }

    return $response['ipn_id'];
}

function createPesapalPayment($accessToken, $ipnId, $order, $env = 'sandbox') {
    $url = $env === 'live'
    ? 'https://pay.pesapal.com/v3/api/Transactions/SubmitOrderRequest'
    : 'https://cybqa.pesapal.com/pesapalv3/api/Transactions/SubmitOrderRequest';

    $payload = json_encode([
        "id" => $order['order_id'],
        "currency" => $order['currency'],
        "amount" => $order['amount'],
        "description" => $order['description'],
        "callback_url" => $order['callback_url'],
        "notification_id" => $ipnId,
        "billing_address" => $order['billing_address']
    ]);

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_HTTPHEADER => [
            "Authorization: Bearer $accessToken",
            "Content-Type: application/json"
        ],
        CURLOPT_POSTFIELDS => $payload,
        CURLOPT_RETURNTRANSFER => true
    ]);
    
    $response = json_decode(curl_exec($ch), true);
    curl_close($ch);

    if (!isset($response['redirect_url'])) {
        throw new Exception("Payment creation failed: " . json_encode($response));
    }

    return $response;
}

function getPesapalTransactionStatus($accessToken, $trackingId, $env = 'sandbox') {
    $url = $env === 'live'
    ? "https://pay.pesapal.com/v3/api/Transactions/GetTransactionStatus?orderTrackingId=$trackingId"
    : "https://cybqa.pesapal.com/pesapalv3/api/Transactions/GetTransactionStatus?orderTrackingId=$trackingId";

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_HTTPHEADER => [
            "Authorization: Bearer $accessToken",
            "Accept: application/json"
        ],
        CURLOPT_RETURNTRANSFER => true
    ]);

    $response = json_decode(curl_exec($ch), true);
    curl_close($ch);

    if (!isset($response['payment_status_description'])) {
        throw new Exception("Failed to get transaction status: " . json_encode($response));
    }

    return $response;
}