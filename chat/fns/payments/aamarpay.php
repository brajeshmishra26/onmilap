<?php

$aamarpay_store_id = $aamarpay_signature_key = null;
$aamarpay_url = 'https://secure.aamarpay.com/index.php';
$aamarpay_test_mode = false;

if (isset($payment_data['credentials']) && !empty($payment_data['credentials'])) {

    $credentials = json_decode($payment_data['credentials']);

    if (!empty($credentials)) {
        if (isset($credentials->aamarpay_store_id)) {
            $aamarpay_store_id = $credentials->aamarpay_store_id;
        }

        if (isset($credentials->aamarpay_signature_key)) {
            $aamarpay_signature_key = $credentials->aamarpay_signature_key;
        }

        if (isset($credentials->aamarpay_test_mode) && $credentials->aamarpay_test_mode === 'yes') {
            $aamarpay_url = 'https://sandbox.aamarpay.com/index.php';
            $aamarpay_test_mode = true;
        }

    }

}

if (empty($aamarpay_store_id) || empty($aamarpay_signature_key)) {
    $result['error_message'] = "Invalid payment method credentials — Contact the webmaster";
    $result['error_key'] = 'invalid_payment_credentials';
    return;
}


if (isset($payment_data['purchase'])) {

    $currency = Registry::load('settings')->default_currency;

    if (!in_array(Registry::load('settings')->default_currency, array('USD', 'BDT'))) {

        $currency = 'BDT';

        include_once "fns/currency_tools/load.php";
        $payment_data['purchase'] = currency_converter($payment_data['purchase'], Registry::load('settings')->default_currency, 'BDT');

        if (empty($payment_data['purchase'])) {
            $result['error_message'] = "Currency conversion was unsuccessful.";
            $result['error_key'] = 'invalid_payment_credentials';
            return;
        }
    }


    try {

        $ammarpay_data = [
            'store_id' => $aamarpay_store_id,
            'signature_key' => $aamarpay_signature_key,
            'cus_name' => Registry::load('current_user')->name,
            'cus_email' => Registry::load('current_user')->email_address,
            'cus_phone' => '0123456789',
            'amount' => $payment_data['purchase'],
            'currency' => $currency,
            'tran_id' => 'wallet_trans_'.$payment_data['wallet_transaction_id'],
            'desc' => $payment_data['transaction_name'],
            'success_url' => $payment_data['validation_url'],
            'fail_url' => $payment_data['validation_url'],
            'cancel_url' => $payment_data['validation_url'],
            'type' => 'json'
        ];


        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => $aamarpay_url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => $ammarpay_data));

        $response = curl_exec($curl);
        curl_close($curl);
        $session = json_decode($response, true);

        if (!empty($session) && isset($session['payment_url']) && !empty($session['payment_url'])) {
            $result['redirect'] = $session['payment_url'];
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
    $session_id = 'wallet_trans_'.$payment_data['validate_purchase'];

    if (!empty($session_id)) {

        try {
            $ammarpay_url = 'https://secure.aamarpay.com/api/v1/trxcheck/request.php';

            if ($aamarpay_test_mode) {
                $ammarpay_url = 'https://sandbox.aamarpay.com/api/v1/trxcheck/request.php';
            }

            $ammarpay_url .= '?request_id='.$session_id.'&store_id='.$aamarpay_store_id.'&signature_key='.$aamarpay_signature_key.'&type=json';


            $curl_handle = curl_init();
            curl_setopt($curl_handle, CURLOPT_URL, $ammarpay_url);

            curl_setopt($curl_handle, CURLOPT_VERBOSE, true);
            curl_setopt($curl_handle, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl_handle, CURLOPT_SSL_VERIFYPEER, false);
            $response = curl_exec($curl_handle);
            curl_close($curl_handle);


            $payment_intent = json_decode($response, true);

            if (!empty($payment_intent) && isset($payment_intent['status_code'])) {

                if ((int)$payment_intent['status_code'] === 2) {
                    $result = array();
                    $result['success'] = true;
                    $result['transaction_info'] = $payment_intent;
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