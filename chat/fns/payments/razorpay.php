<?php
include_once 'fns/payments/razorpay/autoload.php';

use Razorpay\Api\Api;

$razorpay_api_key = null;

if (isset($payment_data['credentials']) && !empty($payment_data['credentials'])) {

    $credentials = json_decode($payment_data['credentials']);

    if (!empty($credentials)) {
        if (isset($credentials->razorpay_api_key)) {
            $razorpay_api_key = $credentials->razorpay_api_key;
            $razorpay_secret_key = $credentials->razorpay_secret_key;
        }

    }

}


if (empty($razorpay_api_key)) {
    $result['error_message'] = "Invalid payment method credentials — Contact the webmaster";
    $result['error_key'] = 'invalid_payment_credentials';
    return;
}

if (isset($payment_data['purchase'])) {

    $currency = Registry::load('settings')->default_currency;

    include_once "fns/data_arrays/razorpay_currencies.php";

    if (!in_array(Registry::load('settings')->default_currency, $razorpay_currencies)) {

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

        $razorpay_api = new Api($razorpay_api_key, $razorpay_secret_key);
        $payment_data['purchase'] = (int) $payment_data['purchase'];
        $payment_data['purchase'] = $payment_data['purchase'] * 100;

        $session = $razorpay_api->order->create([
            'receipt' => $payment_data['wallet_transaction_id'],
            'amount' => $payment_data['purchase'],
            'currency' => $currency
        ]);

        $order_id = $session['id'];


        $payment_session_data = array();
        $payment_session_data["rp_order_id"] = $order_id;
        $payment_session_data["payment_session_id"] = $order_id;
        $payment_session_data["rp_api_key"] = $razorpay_api_key;
        $payment_session_data["rp_currency"] = $currency;
        $payment_session_data["rp_amount"] = $payment_data['purchase'];
        $payment_session_data["rp_name"] = $payment_data['transaction_name'];
        $payment_session_data["rp_description"] = $payment_data['transaction_name'];
        $payment_session_data["rp_callback"] = $payment_data['validation_url'];

        $payment_session_data = json_encode($payment_session_data);
        DB::connect()->update('site_users_wallet', ['transaction_info' => $payment_session_data], ['wallet_transaction_id' => $payment_data['wallet_transaction_id']]);

        $result['redirect'] = Registry::load('config')->site_url . 'topup_wallet/?razorpay=' . $payment_data['wallet_transaction_id'];
        return;

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


    if (isset($payment_data["transaction_info"])) {
        $transaction_info = json_decode($payment_data['transaction_info'], true);
        $session_id = $transaction_info["rp_order_id"];
    }


    if (!empty($session_id)) {

        try {

            $razorpay_api = new Api($razorpay_api_key, $razorpay_secret_key);

            $payment_intent = $razorpay_api->order->fetch($session_id);

            if (in_array(strtolower($payment_intent->status), ['paid', 'captured', 'completed'])) {
                $result = array();
                $result['success'] = true;
                $result['transaction_info'] = $transaction_info;
            } else {
                $result['error'] = 'Payment Failed';
            }

        } catch (Exception $e) {
            $result['error'] = $e->getMessage();
        }

    }
}