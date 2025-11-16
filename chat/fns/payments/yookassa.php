<?php

$yookassa_shop_id = $yookassa_secret_key = null;
$yookassa_url = 'https://api.yookassa.ru/v3/payments';

if (isset($payment_data['credentials']) && !empty($payment_data['credentials'])) {

    $credentials = json_decode($payment_data['credentials']);

    if (!empty($credentials)) {
        if (isset($credentials->yookassa_shop_id)) {
            $yookassa_shop_id = $credentials->yookassa_shop_id;
        }

        if (isset($credentials->yookassa_secret_key)) {
            $yookassa_secret_key = $credentials->yookassa_secret_key;
        }

    }

}

if (empty($yookassa_shop_id) || empty($yookassa_secret_key)) {
    $result['error_message'] = "Invalid payment method credentials — Contact the webmaster";
    $result['error_key'] = 'invalid_payment_credentials';
    return;
}


if (isset($payment_data['purchase'])) {

    $currency = Registry::load('settings')->default_currency;

    if (!in_array(Registry::load('settings')->default_currency, array('RUB'))) {

        $currency = 'RUB';

        include_once "fns/currency_tools/load.php";
        $payment_data['purchase'] = currency_converter($payment_data['purchase'], Registry::load('settings')->default_currency, 'RUB');

        if (empty($payment_data['purchase'])) {
            $result['error_message'] = "Currency conversion was unsuccessful.";
            $result['error_key'] = 'invalid_payment_credentials';
            return;
        }
    }


    try {

        $purchase_amount = number_format((float)$payment_data['purchase'], 2, '.', '');


        $checkout_data = [
            'amount' => [
                'value' => $purchase_amount,
                'currency' => $currency,
            ],
            'capture_mode' => 'AUTOMATIC',
            'confirmation' => [
                'type' => 'redirect',
                'return_url' => $payment_data['validation_url'],
            ],
            'description' => $payment_data['transaction_name'],
        ];

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $yookassa_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Basic ' . base64_encode($yookassa_shop_id . ':' . $yookassa_secret_key),
            'Content-Type: application/json',
        ]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($checkout_data));

        $response = curl_exec($ch);

        $session = json_decode($response, true);


        if (!empty($session) && isset($session['confirmation']['confirmation_url'])) {

            $payment_session_data = array();
            $payment_session_data["payment_session_id"] = $session['id'];

            $payment_session_data = json_encode($payment_session_data);
            DB::connect()->update('site_users_wallet', ['transaction_info' => $payment_session_data], ['wallet_transaction_id' => $payment_data['wallet_transaction_id']]);


            $result['redirect'] = $session['confirmation']['confirmation_url'];
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

            $yookassa_url = "https://api.yookassa.ru/v3/payments/{$session_id}";

            $ch = curl_init();

            curl_setopt($ch, CURLOPT_URL, $yookassa_url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Authorization: Basic ' . base64_encode($yookassa_shop_id . ':' . $yookassa_secret_key),
            ]);

            $response = curl_exec($ch);


            $payment_intent = json_decode($response, true);

            if (!empty($payment_intent) && isset($payment_intent['status'])) {

                if ($payment_intent['status'] === "succeeded") {
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