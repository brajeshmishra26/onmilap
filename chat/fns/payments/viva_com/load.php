<?php
function generate_viva_access_token($client_id, $client_secret, $test_mode)
{

    $curl_url = 'https://accounts.vivapayments.com/connect/token';

    if ($test_mode) {
        $curl_url = 'https://demo-accounts.vivapayments.com/connect/token';
    }

    $result = array();
    $result['success'] = false;
    $authorization = base64_encode($client_id . ':' . $client_secret);
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $curl_url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $headers = [
        'Content-Type: application/x-www-form-urlencoded',
        'Authorization: Basic ' . $authorization
    ];
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

    $post_fields = http_build_query([
        'grant_type' => 'client_credentials'
    ]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $post_fields);

    $response = curl_exec($ch);

    if (curl_errno($ch)) {
        $result['success'] = false;
    } else {
        $response = json_decode($response, true);

        if (!empty($response) && isset($response['access_token']) && !empty($response['access_token'])) {
            $result = array();
            $result['success'] = true;
            $result['access_token'] = $response['access_token'];
        }
    }

    curl_close($ch);

    return $result;
}