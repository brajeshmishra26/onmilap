<?php

$maya_public_api_key = $maya_secret_api_key = null;
$maya_url = 'https://pg.paymaya.com/checkout/v1/checkouts';
$maya_test_mode = false;

if (isset($payment_data['credentials']) && !empty($payment_data['credentials'])) {

    $credentials = json_decode($payment_data['credentials']);

    if (!empty($credentials)) {
        if (isset($credentials->maya_public_api_key)) {
            $maya_public_api_key = $credentials->maya_public_api_key;
        }

        if (isset($credentials->maya_secret_api_key)) {
            $maya_secret_api_key = $credentials->maya_secret_api_key;
        }

        if (isset($credentials->maya_test_mode) && $credentials->maya_test_mode === 'yes') {
            $maya_url = 'https://pg-sandbox.paymaya.com/checkout/v1/checkouts';
            $maya_test_mode = true;
        }

    }

}

if (empty($maya_public_api_key) || empty($maya_secret_api_key)) {
    $result['error_message'] = "Invalid payment method credentials — Contact the webmaster";
    $result['error_key'] = 'invalid_payment_credentials';
    return;
}


if (isset($payment_data['purchase'])) {

    $currency = Registry::load('settings')->default_currency;

    if (!in_array(Registry::load('settings')->default_currency, array('PHP'))) {

        $currency = 'PHP';

        include_once "fns/currency_tools/load.php";
        $payment_data['purchase'] = currency_converter($payment_data['purchase'], Registry::load('settings')->default_currency, 'PHP');

        if (empty($payment_data['purchase'])) {
            $result['error_message'] = "Currency conversion was unsuccessful.";
            $result['error_key'] = 'invalid_payment_credentials';
            return;
        }
    }


    try {

        $purchase_amount = number_format((float)$payment_data['purchase'], 2, '.', '');

        $checkoutData = [
            'totalAmount' => [
                'value' => $purchase_amount,
                'currency' => $currency,
            ],
            'buyer' => [
                'firstName' => Registry::load('current_user')->name,
                'email' => Registry::load('current_user')->email_address,
            ],
            'items' => [
                [
                    'name' => $payment_data['transaction_name'],
                    'quantity' => 1,
                    'totalAmount' => [
                        'value' => $purchase_amount
                    ],
                ],
            ],
            'redirectUrl' => [
                'success' => $payment_data['validation_url'],
                'failure' => $payment_data['validation_url'],
                'cancel' => $payment_data['validation_url'],
            ],
            'requestReferenceNumber' => 'wallet_trans_'.$payment_data['wallet_transaction_id'],
        ];


        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => $maya_url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Basic ' . base64_encode($maya_public_api_key . ':' . $maya_secret_api_key),
            ],
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => json_encode($checkoutData)));


        $response = curl_exec($curl);
        curl_close($curl);
        $session = json_decode($response, true);

        if (!empty($session) && isset($session['redirectUrl']) && !empty($session['redirectUrl'])) {

            $payment_session_data = array();
            $payment_session_data["payment_session_id"] = $session['checkoutId'];

            $payment_session_data = json_encode($payment_session_data);
            DB::connect()->update('site_users_wallet', ['transaction_info' => $payment_session_data], ['wallet_transaction_id' => $payment_data['wallet_transaction_id']]);


            $result['redirect'] = $session['redirectUrl'];
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
    $session_id = null;

    if (isset($payment_data["payment_session_id"])) {
        $session_id = $payment_data["payment_session_id"];
        $transaction_info['payment_session_id'] = $session_id;
    }

    if (!empty($session_id)) {

        try {
            $maya_url = 'https://pg.paymaya.com/payments/v1/payments/';

            if ($maya_test_mode) {
                $maya_url = 'https://pg-sandbox.paymaya.com/payments/v1/payments/';
            }

            $maya_url .= $session_id.'/status';


            $curl_handle = curl_init();
            curl_setopt($curl_handle, CURLOPT_URL, $maya_url);

            curl_setopt($curl_handle, CURLOPT_VERBOSE, true);
            curl_setopt($curl_handle, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl_handle, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($curl_handle, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'Authorization: Basic ' . base64_encode($maya_public_api_key . ':' . $maya_secret_api_key),
            ]);
            $response = curl_exec($curl_handle);
            curl_close($curl_handle);


            $payment_intent = json_decode($response, true);

            if (!empty($payment_intent) && isset($payment_intent['status'])) {

                if ($payment_intent['status'] === "PAYMENT_SUCCESS") {
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