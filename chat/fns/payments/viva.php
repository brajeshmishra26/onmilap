<?php

include('fns/payments/viva_com/load.php');

$viva_client_id = $viva_client_secret = null;
$viva_merchant_id = $viva_api_key = null;
$viva_test_mode = false;

if (isset($payment_data['credentials']) && !empty($payment_data['credentials'])) {

    $credentials = json_decode($payment_data['credentials']);

    if (!empty($credentials)) {
        if (isset($credentials->viva_client_id)) {
            $viva_client_id = $credentials->viva_client_id;
        }

        if (isset($credentials->viva_client_secret)) {
            $viva_client_secret = $credentials->viva_client_secret;
        }

        if (isset($credentials->viva_merchant_id)) {
            $viva_merchant_id = $credentials->viva_merchant_id;
        }

        if (isset($credentials->viva_api_key)) {
            $viva_api_key = $credentials->viva_api_key;
        }


        if (isset($credentials->viva_test_mode) && $credentials->viva_test_mode === 'yes') {
            $viva_test_mode = true;
        }

    }

}

if (empty($viva_client_id) || empty($viva_client_secret) || empty($viva_merchant_id) || empty($viva_api_key)) {
    $result['error_message'] = "Invalid payment method credentials — Contact the webmaster";
    $result['error_key'] = 'invalid_payment_credentials';
    return;
}


if (isset($payment_data['purchase'])) {

    $token = generate_viva_access_token($viva_client_id, $viva_client_secret, $viva_test_mode);
    $redirect_validation = true;

    if (isset($token['access_token'])) {
        $token = $token['access_token'];
    } else {
        $token = null;
    }

    if (!empty($token)) {

        $curl_url = 'https://api.vivapayments.com/checkout/v2/orders';

        if ($viva_test_mode) {
            $curl_url = 'https://demo-api.vivapayments.com/checkout/v2/orders';
        }
        $currency_symbols = [
            'HRK' => 191, 'CZK' => 203, 'DKK' => 208, 'HUF' => 348, 'SEK' => 752, 'GBP' => 826,
            'RON' => 946, 'BGN' => 975, 'EUR' => 978, 'PLN' => 985
        ];

        $currency_code = 826;

        $currency = Registry::load('settings')->default_currency;

        if (isset($currency_symbols[$currency])) {
            $currency_code = $currency_symbols[$currency];
        } else {
            $currency = 'GBP';
            $currency_code = 826;

            include_once "fns/currency_tools/load.php";
            $payment_data['purchase'] = currency_converter($payment_data['purchase'], Registry::load('settings')->default_currency, $currency);

            if (empty($payment_data['purchase'])) {
                $result['error_message'] = "Currency conversion was unsuccessful.";
                $result['error_key'] = 'invalid_payment_credentials';
                return;
            }
        }

        $amount = $payment_data['purchase'];
        $amountcents = round((float)$amount * 100);

        $postFields = [
            'amount' => $amountcents,
            'customerTrns' => $payment_data['transaction_name'],
            'CurrencyCode' => $currency_code,
            'customer' => [
                'email' => Registry::load('current_user')->email_address,
                'fullName' => Registry::load('current_user')->name,
            ],

            'merchantTrns' => $payment_data['transaction_name']
        ];

        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => $curl_url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => json_encode($postFields),
            CURLOPT_HTTPHEADER => array(
                "Authorization: Bearer $token",
                'Content-Type: application/json'
            ),
        ));

        $response = curl_exec($curl);
        curl_close($curl);

        $response = json_decode($response, true);

        $payment_url = 'https://www.vivapayments.com/web/checkout?ref=';

        if ($viva_test_mode) {
            $payment_url = 'https://demo.vivapayments.com/web/checkout?ref=';
        }

        if (!empty($response) && isset($response['orderCode']) && !empty($response['orderCode'])) {
            $redirect_validation = false;

            $cookie_value = $payment_data['wallet_transaction_id'];
            setcookie('current_wallet_tp_trans', $cookie_value, time() + (86400 * 30), "/");

            $payment_session_data = array();
            $payment_session_data["payment_session_id"] = $response['orderCode'];

            $payment_session_data = json_encode($payment_session_data);
            DB::connect()->update('site_users_wallet', ['transaction_info' => $payment_session_data], ['wallet_transaction_id' => $payment_data['wallet_transaction_id']]);


            $result['redirect'] = $payment_url.$response['orderCode'];
            return;
        }
    }

    if ($redirect_validation) {
        $result['redirect'] = $payment_data['validation_url'];
        return;
    }
} else if (isset($payment_data['validate_purchase'])) {
    $transaction_info = array_merge($_GET, $_POST);

    if (isset($payment_data['payment_session_id']) && !empty($payment_data['payment_session_id'])) {
        $transaction_info['payment_session_id'] = $payment_data['payment_session_id'];
    }

    $result = array();
    $result['success'] = false;
    $result['transaction_info'] = $transaction_info;
    $result['error'] = 'something_went_wrong';

    if (isset($payment_data['payment_session_id']) && !empty($payment_data['payment_session_id'])) {

        $curl_url = 'https://www.vivapayments.com/api/orders/'.$payment_data['payment_session_id'];

        if ($viva_test_mode) {
            $curl_url = 'https://demo.vivapayments.com/api/orders/'.$payment_data['payment_session_id'];
        }

        $token = $viva_merchant_id . ':' . $viva_api_key;
        $token = base64_encode($token);

        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => $curl_url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_HTTPHEADER => array(
                "Authorization: Basic $token",
                'Content-Type: application/json'
            ),
        ));

        $response = curl_exec($curl);
        curl_close($curl);

        $response = json_decode($response, true);

        if (!empty($response) && isset($response['StateId']) && !empty($response['StateId'])) {
            if ((int)$response['StateId'] === 3) {
                $result = array();
                $result['success'] = true;
                $result['transaction_info'] = $transaction_info;
            }
        }
    }
}