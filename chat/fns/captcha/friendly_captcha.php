<?php
$verification_URL = 'https://global.frcapi.com/api/v2/captcha/siteverify';
$post_data = http_build_query(
    array(
        'response' => $validate,
    )
);

$captcha_secret_key = Registry::load('settings')->captcha_secret_key;

if (function_exists('curl_init') && function_exists('curl_setopt') && function_exists('curl_exec')) {
    $curl_request = curl_init($verification_URL);
    curl_setopt($curl_request, CURLOPT_POST, 1);
    curl_setopt($curl_request, CURLOPT_POSTFIELDS, $post_data);
    curl_setopt($curl_request, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($curl_request, CURLOPT_SSL_VERIFYPEER, 0);
    curl_setopt($curl_request, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
    curl_setopt($curl_request, CURLOPT_CONNECTTIMEOUT, 5);
    curl_setopt($curl_request, CURLOPT_TIMEOUT, 5);
    curl_setopt($curl_request, CURLOPT_HTTPHEADER, [
        'Accept: application/json',
        'Content-Type: application/x-www-form-urlencoded',
        'X-API-Key: ' . $captcha_secret_key
    ]);

    $response = curl_exec($curl_request);
    curl_close($curl_request);
} else {
    $opts = ['http' =>
        [
            'method' => 'POST',
            'header' => "Content-Type: application/x-www-form-urlencoded\r\n" .
            "X-API-Key: $captcha_secret_key\r\n",
            'content' => $post_data
        ]
    ];
    $context = stream_context_create($opts);
    $response = file_get_contents($verification_URL, false, $context);
}
if ($response) {
    $captcha_result = json_decode($response);
    if (isset($captcha_result->success) && $captcha_result->success === true) {
        $result = true;
    }
}