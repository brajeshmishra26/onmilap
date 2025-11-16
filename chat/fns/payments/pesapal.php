<?php

include_once 'fns/payments/pesapal/load.php';

$pesapal_consumer_key = $pesapal_consumer_secret = $pesapal_ipn_id = null;
$pesapal_env = 'live';

if (isset($payment_data['credentials']) && !empty($payment_data['credentials'])) {

    $credentials = json_decode($payment_data['credentials']);

    if (!empty($credentials)) {
        if (isset($credentials->pesapal_consumer_key)) {
            $pesapal_consumer_key = $credentials->pesapal_consumer_key;
        }

        if (isset($credentials->pesapal_consumer_secret)) {
            $pesapal_consumer_secret = $credentials->pesapal_consumer_secret;
        }

        if (isset($credentials->pesapal_ipn_id)) {
            $pesapal_ipn_id = $credentials->pesapal_ipn_id;
        }

        if (isset($credentials->pesapal_test_mode) && $credentials->pesapal_test_mode === 'yes') {
            $pesapal_env = 'sandbox';
        }

    }

}

if (empty($pesapal_consumer_key) || empty($pesapal_consumer_secret)) {
    $result['error_message'] = "Invalid payment method credentials — Contact the webmaster";
    $result['error_key'] = 'invalid_payment_credentials';
    return;
}

if (isset($payment_data['purchase'])) {

    $currency = Registry::load('settings')->default_currency;

    $pesapal_currencies = ['KES', 'MWK', 'TZS', 'RWF', 'UGX', 'ZMW', 'ZAR', 'USD',];

    if (!in_array(Registry::load('settings')->default_currency, $pesapal_currencies)) {

        $currency = 'USD';

        include_once "fns/currency_tools/load.php";
        $payment_data['purchase'] = currency_converter($payment_data['purchase'], Registry::load('settings')->default_currency);

        if (empty($payment_data['purchase'])) {
            $result['error_message'] = "Currency conversion was unsuccessful.";
            $result['error_key'] = 'invalid_payment_credentials';
            return;
        }
    }

    try {
        $access_token = getPesapalAccessToken($pesapal_consumer_key, $pesapal_consumer_secret, $pesapal_env);

        if (empty($pesapal_ipn_id)) {
            $pesapal_ipn_id = registerPesapalIPN($access_token, $payment_data['validation_url'], $env);

            if (isset($payment_data['gateway_id']) && !empty($payment_data['gateway_id'])) {
                $payment_credentials['pesapal_consumer_key'] = $pesapal_consumer_key;
                $payment_credentials['pesapal_consumer_secret'] = $pesapal_consumer_secret;
                $payment_credentials['pesapal_ipn_id'] = $pesapal_ipn_id;
                $payment_credentials['pesapal_test_mode'] = $credentials->pesapal_test_mode;

                DB::connect()->update('payment_gateways', ['credentials' => $payment_credentials], ['payment_gateway_id' => $payment_data['gateway_id']]);
            }

        }

        $billingAddress = [
            "email_address" => Registry::load('current_user')->email_address,
            "first_name" => Registry::load('current_user')->name,
        ];

        $order = [
            "order_id" => $payment_data['wallet_transaction_id'],
            "currency" => $currency,
            "amount" => $payment_data['purchase'],
            "description" => $payment_data['transaction_name'],
            "callback_url" => $payment_data['validation_url'],
            "billing_address" => $billingAddress
        ];

        if (isset($credentials->pesapal_test_mode) && $credentials->pesapal_test_mode === 'yes') {
            $order['order_id'] = uniqid();
        }

        $payment_info = createPesapalPayment($access_token, $pesapal_ipn_id, $order, $pesapal_env);

        $payment_session_data = array();
        $payment_session_data["payment_session_id"] = $payment_info['order_tracking_id'];

        $payment_session_data = json_encode($payment_session_data);
        DB::connect()->update('site_users_wallet', ['transaction_info' => $payment_session_data], ['wallet_transaction_id' => $payment_data['wallet_transaction_id']]);

        $result['redirect'] = $payment_info['redirect_url'];

    } catch (Exception $e) {
        $result['redirect'] = $payment_data['validation_url'];
        return;
    }
} else if (isset($payment_data['validate_purchase'])) {

    $transaction_info = array_merge($_GET, $_POST);

    $result = array();
    $result['success'] = false;
    $result['transaction_info'] = $transaction_info;
    $result['error'] = 'something_went_wrong';

    $transaction_info = array_merge($_GET, $_POST);

    $result = array();
    $result['success'] = false;
    $result['transaction_info'] = $transaction_info;
    $result['error'] = 'something_went_wrong';
    $session_id = null;


    if (isset($payment_data["payment_session_id"])) {
        $session_id = $payment_data["payment_session_id"];
        $transaction_info['payment_session_id'] = $session_id;
    }
    if (!empty($session_id)) {

        try {
            $access_token = getPesapalAccessToken($pesapal_consumer_key, $pesapal_consumer_secret, $pesapal_env);
            $payment_info = getPesapalTransactionStatus($access_token, $session_id, $pesapal_env);

            if (isset($payment_info['status_code']) && (int)$payment_info['status_code'] === 1) {
                $result = array();
                $result['success'] = true;
                $result['transaction_info'] = $transaction_info;
            }
        } catch (Exception $e) {
            $result['error'] = $e->getMessage();
            return;
        }
    }
}