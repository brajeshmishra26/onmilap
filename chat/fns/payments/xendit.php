<?php
include_once 'fns/payments/xendit/autoload.php';

use Xendit\Configuration;
use Xendit\Invoice\InvoiceApi;
use Xendit\Invoice\CreateInvoiceRequest;


$secret_key = null;

if (isset($payment_data['credentials']) && !empty($payment_data['credentials'])) {

    $credentials = json_decode($payment_data['credentials']);

    if (!empty($credentials)) {
        if (isset($credentials->xendit_api_key)) {
            $secret_key = $credentials->xendit_api_key;
        }

    }

}


if (empty($secret_key)) {
    $result['error_message'] = "Invalid payment method credentials — Contact the webmaster";
    $result['error_key'] = 'invalid_payment_credentials';
    return;
}

if (isset($payment_data['purchase'])) {

    try {
        Configuration::setXenditKey($secret_key);

        $apiInstance = new InvoiceApi();

        $payment_currency = Registry::load('settings')->default_currency;

        $xendit_currencies = ['IDR', 'PHP', 'USD', 'VND', 'THB', 'MYR', 'SGD'];

        if (!in_array($payment_currency, $xendit_currencies)) {

            $payment_currency = 'USD';

            include_once "fns/currency_tools/load.php";
            $payment_data['purchase'] = currency_converter($payment_data['purchase'], Registry::load('settings')->default_currency);

            if (empty($payment_data['purchase'])) {
                $result['error_message'] = "Currency conversion was unsuccessful.";
                $result['error_key'] = 'invalid_payment_credentials';
                return;
            }
        }

        $requestData = [
            'external_id' => $payment_data['wallet_transaction_id'],
            'description' => $payment_data['transaction_name'],
            'amount' => $payment_data['purchase'],
            'currency' => $payment_currency,
            'success_redirect_url' => $payment_data['validation_url'],
            'failure_redirect_url' => $payment_data['validation_url'],
        ];
        $create_invoice_request = new CreateInvoiceRequest($requestData);
        $inv_result = $apiInstance->createInvoice($create_invoice_request);


        if (isset($inv_result['invoice_url'])) {

            $invoiceUrl = $inv_result['invoice_url'];

            $payment_session_data = array();
            $payment_session_data["payment_session_id"] = $inv_result['id'];
            $payment_session_data["payment_external_id"] = $inv_result['external_id'];
            $payment_session_data = json_encode($payment_session_data);

            DB::connect()->update('site_users_wallet', ['transaction_info' => $payment_session_data], ['wallet_transaction_id' => $payment_data['wallet_transaction_id']]);

            $result['redirect'] = $invoiceUrl;
            return;
        } else {
            $result['redirect'] = $payment_data['validation_url'];
            return;
        }
    } catch (\Xendit\XenditSdkException $e) {

        if (isset($debug_method) && $debug_method) {
            $result['error_message'] = 'Exception when calling InvoiceApi->createInvoice: '.$e->getMessage();
            return;
        }

        $error_log = $e->getFullError();

        if ($error_log->errorCode === 'UNSUPPORTED_CURRENCY') {
            $result['error_message'] = $payment_currency." Currency Not Supported. Contact WebMaster";
            return;
        }

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

            Configuration::setXenditKey($secret_key);
            $apiInstance = new InvoiceApi();
            $inv_result = $apiInstance->getInvoiceById($session_id);

            if ($inv_result['status'] === 'PAID') {
                $result = array();
                $result['success'] = true;
                $result['transaction_info'] = $transaction_info;
            } else {
                $result['error'] = $inv_result['status'];
            }

        } catch (\Xendit\XenditSdkException $e) {

            if (isset($debug_method) && $debug_method) {
                $result['error_message'] = 'Exception when calling InvoiceApi->createInvoice: '. $e->getMessage();
            }
            $result['error'] = $e->getMessage();
        }
    }
}