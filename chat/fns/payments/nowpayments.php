<?php

$nowpayments_api_key = null;
$nowpayments_url = 'https://api.nowpayments.io/v1/';

if (isset($payment_data['credentials']) && !empty($payment_data['credentials'])) {

    $credentials = json_decode($payment_data['credentials']);

    if (!empty($credentials)) {
        if (isset($credentials->nowpayments_api_key)) {
            $nowpayments_api_key = $credentials->nowpayments_api_key;
        }

        if (isset($credentials->nowpayments_sandbox) && $credentials->nowpayments_sandbox === 'yes') {
            $nowpayments_url = 'https://api-sandbox.nowpayments.io/v1/';
        }

    }

}

if (empty($nowpayments_api_key)) {
    $result['error_message'] = "Invalid payment method credentials — Contact the webmaster";
    $result['error_key'] = 'invalid_payment_credentials';
    return;
}


if (isset($payment_data['purchase'])) {

    $currency = Registry::load('settings')->default_currency;

    if (!in_array(Registry::load('settings')->default_currency, array('USD'))) {

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

        $nowpayments_url = $nowpayments_url.'invoice';
        $nowpayments_data = array(
            'order_id' => $payment_data['wallet_transaction_id'],
            'order_description' => $payment_data['description'],
            'price_amount' => $payment_data['purchase'],
            'price_currency' => $currency,
            'success_url' => $payment_data['validation_url'],
            'cancel_url' => $payment_data['validation_url'],
        );

        $headers = array(
            'Content-Type: application/json',
            'x-api-key: ' . $nowpayments_api_key
        );

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $nowpayments_url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($nowpayments_data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $response = curl_exec($ch);
        curl_close($ch);

        $session = json_decode($response);

        if (!empty($session) && isset($session->invoice_url)) {

            $payment_session_data = array();
            $payment_session_data["payment_session_id"] = $session->id;

            $payment_session_data = json_encode($payment_session_data);
            DB::connect()->update('site_users_wallet', ['transaction_info' => $payment_session_data], ['wallet_transaction_id' => $payment_data['wallet_transaction_id']]);

            $result['redirect'] = $session->invoice_url;
            return;
        } else {
            $result['redirect'] = $payment_data['validation_url'];
            return;
        }
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
    $session_id = $NP_payment_id = null;


    if (isset($payment_data["payment_session_id"])) {
        $session_id = $payment_data["payment_session_id"];
        $transaction_info['payment_session_id'] = $session_id;
    }


    if (isset($_POST['NP_id']) || isset($_GET['NP_id'])) {
        $NP_payment_id = isset($_POST['NP_id']) ? $_POST['NP_id'] : $_GET['NP_id'];
    }

    if (empty($NP_payment_id) && isset($payment_data["NP_id"])) {
        $NP_payment_id = $payment_data["NP_id"];
    }

    if (!empty($NP_payment_id)) {

        try {

            $nowpayments_url = $nowpayments_url.'payment/'.$NP_payment_id;
            $transaction_info['NP_payment_id'] = $NP_payment_id;

            if (!empty($session_id)) {
                $transaction_info['payment_session_id'] = $session_id;
            }

            $headers = array(
                'Content-Type: application/json',
                'x-api-key: ' . $nowpayments_api_key
            );

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $nowpayments_url);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

            $response = curl_exec($ch);
            curl_close($ch);

            $payment_intent = json_decode($response, true);


            if (!empty($payment_intent)) {
                $transaction_info = array_merge($transaction_info, $payment_intent);
            }

            if (!empty($payment_intent) && isset($payment_intent['invoice_id'])) {
                if ((int)$payment_intent['invoice_id'] !== (int)$session_id) {
                    $payment_intent = null;
                }
            }

            if (!empty($payment_intent) && isset($payment_intent['invoice_id'])) {

                if ($payment_intent['payment_status'] === 'finished') {
                    $result = array();
                    $result['success'] = true;
                    $result['transaction_info'] = $transaction_info;
                } else {
                    $result['error'] = 'Payment Status : '.$payment_intent['payment_status'];
                }
            } else {
                $result['error'] = 'Failed Payment';
            }

        } catch (Exception $e) {
            $result['error'] = $e->getMessage();
        }
    }
}