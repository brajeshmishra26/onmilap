<?php

$merchant_id = $merchant_key = $merchant_salt = null;

if (isset($payment_data['credentials']) && !empty($payment_data['credentials'])) {

    $credentials = json_decode($payment_data['credentials']);

    if (!empty($credentials)) {
        if (isset($credentials->paytr_merchant_id)) {
            $merchant_id = $credentials->paytr_merchant_id;
        }

        if (isset($credentials->paytr_merchant_key)) {
            $merchant_key = $credentials->paytr_merchant_key;
        }

        if (isset($credentials->paytr_merchant_salt)) {
            $merchant_salt = $credentials->paytr_merchant_salt;
        }
    }

}

if (empty($merchant_id) || empty($merchant_key) || empty($merchant_salt)) {
    $result['error_message'] = "Invalid payment method credentials — Contact the webmaster";
    $result['error_key'] = 'invalid_payment_credentials';
    return;
}


if (isset($payment_data['purchase'])) {

    $currency = Registry::load('settings')->default_currency;

    $paytr_currencies = [
        'TL', 'EUR', 'USD', 'GBP', 'RUB', 'TRY'
    ];

    if (Registry::load('settings')->default_currency === 'TRY') {
        Registry::load('settings')->default_currency = 'TL';
    }

    if (!in_array(Registry::load('settings')->default_currency, $paytr_currencies)) {

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




        $name = $payment_data['transaction_name'];
        $price = $payment_data['purchase'];
        $link_type = 'product';

        $paytr_token = $name . $price . $currency . '0producten1';
        $callback_id = 'wallet_transaction_'.$payment_data['wallet_transaction_id'];

        $paytr_token = base64_encode(hash_hmac('sha256', $paytr_token . $merchant_salt, $merchant_key, true));

        $post_vals = [
            'merchant_id' => $merchant_id,
            'name' => $name,
            'price' => $price,
            'currency' => $currency,
            'no_installment' => 1,
            'max_installment' => 0,
            'link_type' => 'product',
            'lang' => "en",
            'callback_link' => $payment_data['validation_url'],
            'callback_id' => $callback_id,
            'paytr_token' => $paytr_token
        ];


        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://www.paytr.com/odeme/api/link/create");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_vals);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_FRESH_CONNECT, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 20);

        $response = @curl_exec($ch);

        $session = json_decode($response, true);

        if (!empty($session) && isset($session['status']) && $session['status'] === 'success' && isset($session['id'])) {

            $payment_session_data = array();
            $payment_session_data = $session;
            $payment_session_data["payment_session_id"] = $payment_data['wallet_transaction_id'];

            $payment_session_data = json_encode($payment_session_data);
            DB::connect()->update('site_users_wallet', ['transaction_info' => $payment_session_data], ['wallet_transaction_id' => $payment_data['wallet_transaction_id']]);

            $result['redirect'] = 'https://www.paytr.com/link/'.$session['id'];
            return;
        } else {
            if (!empty($session) && isset($session['status']) && $session['status'] === 'failed' && isset($session['reason'])) {
                $result['error_message'] = $session['reason'];
                return;
            } else {
                $result['redirect'] = $payment_data['validation_url'];
                return;
            }
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
    $session_id = null;
    $merchant_oid = $callback_id = $paytr_callback_hash = $paytr_status = $paytr_total_amount = null;

    if (isset($payment_data["payment_session_id"])) {
        $session_id = $payment_data["payment_session_id"];
        $transaction_info['payment_session_id'] = $session_id;
    }

    if (isset($_POST['merchant_oid']) || isset($_GET['merchant_oid'])) {
        $merchant_oid = isset($_POST['merchant_oid']) ? $_POST['merchant_oid'] : $_GET['merchant_oid'];
    }

    if (isset($_POST['callback_id']) || isset($_GET['callback_id'])) {
        $callback_id = isset($_POST['callback_id']) ? $_POST['callback_id'] : $_GET['callback_id'];
    }

    if (isset($_POST['hash']) || isset($_GET['hash'])) {
        $paytr_callback_hash = isset($_POST['hash']) ? $_POST['hash'] : $_GET['hash'];
    }

    if (isset($_POST['status']) || isset($_GET['status'])) {
        $paytr_status = isset($_POST['status']) ? $_POST['status'] : $_GET['status'];
    }

    if (isset($_POST['total_amount']) || isset($_GET['total_amount'])) {
        $paytr_total_amount = isset($_POST['total_amount']) ? $_POST['total_amount'] : $_GET['total_amount'];
    }

    if (!empty($session_id) && !empty($fw_transaction_id)) {

        try {

            $paytr_hash = base64_encode(hash_hmac('sha256', $callback_id.$merchant_oid.$merchant_salt.$paytr_status.$paytr_total_amount, $merchant_key, true));

            if ($paytr_hash === $paytr_callback_hash) {

                if ($paytr_status == 'success') {
                    $result = array();
                    $result['success'] = true;
                    $result['transaction_info'] = $transaction_info;
                } else {
                    $result['error'] = 'Failed Payment';
                }
            } else {
                $result['error'] = 'Failed Payment';
            }

        } catch (Exception $e) {
            $result['error'] = $e->getMessage();
        }
    }
}