<?php
include_once('fns/payments/paysera/WebToPay.php');

$project_id = $project_password = null;

if (isset($payment_data['credentials']) && !empty($payment_data['credentials'])) {

    $credentials = json_decode($payment_data['credentials']);

    if (!empty($credentials)) {
        if (isset($credentials->paysera_project_id)) {
            $project_id = $credentials->paysera_project_id;
        }

        if (isset($credentials->paysera_project_password)) {
            $project_password = $credentials->paysera_project_password;
        }
    }

}

if (empty($project_id) || empty($project_password)) {
    $result['error_message'] = "Invalid payment method credentials — Contact the webmaster";
    $result['error_key'] = 'invalid_payment_credentials';
    return;
}

if (isset($payment_data['purchase'])) {

    $currency = Registry::load('settings')->default_currency;

    try {


        $paysera_data = [
            'projectid' => $project_id,
            'sign_password' => $project_password,
            'orderid' => $payment_data['wallet_transaction_id'],
            'amount' => $payment_data['purchase'],
            'currency' => $currency,
            'accepturl' => $payment_data['validation_url'],
            'cancelurl' => $payment_data['validation_url'],
            'callbackurl' => $payment_data['validation_url'],
            'test' => 0,
        ];
        $factory = new WebToPay_Factory(['projectId' => $project_id, 'password' => $project_password]);
        $payment_url = $factory->getRequestBuilder()->buildRequestUrlFromData($paysera_data);


        if (empty($payment_url)) {
            $payment_url = $payment_data['validation_url'];
        }

        $payment_session_data = array();
        $payment_session_data["payment_session_id"] = $payment_data['wallet_transaction_id'];

        $payment_session_data = json_encode($payment_session_data);
        DB::connect()->update('site_users_wallet', ['transaction_info' => $payment_session_data], ['wallet_transaction_id' => $payment_data['wallet_transaction_id']]);

        $result['redirect'] = $payment_url;
        return;

    } catch (Exception $exception) {
        $result['redirect'] = $payment_data['validation_url'];
        return;
    }
} else if (isset($payment_data['validate_purchase'])) {

    $transaction_info = array_merge($_GET, $_POST);

    $result = array();
    $result['success'] = false;
    $result['transaction_info'] = $transaction_info;
    $result['error'] = 'something_went_wrong';

    if (!empty($_REQUEST)) {

        try {

            $response = WebToPay::validateAndParseData($_REQUEST, $project_id, $project_password);

            if (isset($response['status']) && $response['status'] === '1' || isset($response['status']) && $response['status'] === '3') {
                $result = array();
                $result['success'] = true;
                $result['transaction_info'] = $transaction_info;
            } else {
                $result['error'] = 'Failed Payment';
            }

        } catch (Exception $e) {
            $result['error'] = $e->getMessage();
        }
    }
}