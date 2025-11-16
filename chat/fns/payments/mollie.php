<?php
include "fns/payments/mollie/autoload.php";

$mollie_api_key = null;

if (isset($payment_data['credentials']) && !empty($payment_data['credentials'])) {

    $credentials = json_decode($payment_data['credentials']);

    if (!empty($credentials)) {
        if (isset($credentials->mollie_api_key)) {
            $mollie_api_key = $credentials->mollie_api_key;
        }

    }

}


if (empty($mollie_api_key)) {
    $result['error_message'] = "Invalid payment method credentials — Contact the webmaster";
    $result['error_key'] = 'invalid_payment_credentials';
    return;
}


use Mollie\Api\Http\Data\Money;
use Mollie\Api\Http\Requests\CreatePaymentRequest;

$mollie = new \Mollie\Api\MollieApiClient();
$mollie->setApiKey($mollie_api_key);

if (isset($payment_data['purchase'])) {

    $currency = Registry::load('settings')->default_currency;

    include_once "fns/data_arrays/mollie_currencies.php";

    if (!in_array(Registry::load('settings')->default_currency, $mollie_currencies)) {

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
        $purchase_amount = (float)$payment_data['purchase'];
        $purchase_amount = number_format((float)$purchase_amount, 2, '.', '');

        $mollie_payment = $mollie->send(
            new CreatePaymentRequest(
                description: $payment_data['description'],
                amount: new Money(currency: $currency, value: $purchase_amount),
                redirectUrl: $payment_data['validation_url'],
                cancelUrl: $payment_data['validation_url'],
                metadata: ['order_id' => $payment_data['wallet_transaction_id']]
            )
        );

        $payment_session_data = array();
        $payment_session_data["payment_session_id"] = $mollie_payment->id;

        $payment_session_data = json_encode($payment_session_data);
        DB::connect()->update('site_users_wallet', ['transaction_info' => $payment_session_data], ['wallet_transaction_id' => $payment_data['wallet_transaction_id']]);

        $result['redirect'] = $mollie_payment->getCheckoutUrl();
    } catch (\Mollie\Api\Exceptions\ApiException $e) {
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

            $payment_intent = $mollie->payments->get($session_id);
            if ($payment_intent->status === 'paid') {
                $result = array();
                $result['success'] = true;
                $result['transaction_info'] = $transaction_info;
            } else {
                $result['error'] = $payment_intent->status;
            }

        } catch (\Mollie\Api\Exceptions\ApiException $e) {
            $result['error'] = $e->getMessage();
        }
    }
}