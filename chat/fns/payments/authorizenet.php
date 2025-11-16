<?php

include 'fns/payments/authorizenet/autoload.php';

use net\authorize\api\contract\v1 as AnetAPI;
use net\authorize\api\controller as AnetController;

$authorizenet_merchant_login_id = $authorizenet_merchant_transaction_key = null;
$authorizenet_url = 'https://accept.authorize.net/payment/payment';
$authorizenet_test_mode = false;

if (isset($payment_data['credentials']) && !empty($payment_data['credentials'])) {

    $credentials = json_decode($payment_data['credentials']);

    if (!empty($credentials)) {
        if (isset($credentials->authorizenet_merchant_login_id)) {
            $authorizenet_merchant_login_id = $credentials->authorizenet_merchant_login_id;
        }

        if (isset($credentials->authorizenet_merchant_transaction_key)) {
            $authorizenet_merchant_transaction_key = $credentials->authorizenet_merchant_transaction_key;
        }

        if (isset($credentials->authorizenet_test_mode) && $credentials->authorizenet_test_mode === 'yes') {
            $authorizenet_url = 'https://test.authorize.net/payment/payment';
            $authorizenet_test_mode = true;
        }

    }

}

if (empty($authorizenet_merchant_login_id) || empty($authorizenet_merchant_transaction_key)) {
    $result['error_message'] = "Invalid payment method credentials — Contact the webmaster";
    $result['error_key'] = 'invalid_payment_credentials';
    return;
}


if (isset($payment_data['purchase'])) {

    $currency = Registry::load('settings')->default_currency;

    try {


        $merchantAuthentication = new AnetAPI\MerchantAuthenticationType();
        $merchantAuthentication->setName($authorizenet_merchant_login_id);
        $merchantAuthentication->setTransactionKey($authorizenet_merchant_transaction_key);

        $refId = 'wallet_trans_'.$payment_data['wallet_transaction_id'];

        $transactionRequestType = new AnetAPI\TransactionRequestType();
        $transactionRequestType->setTransactionType("authCaptureTransaction");
        $transactionRequestType->setAmount($payment_data['purchase']);
        $transactionRequestType->setCurrencyCode($currency);

        $setting1 = new AnetAPI\SettingType();
        $setting1->setSettingName("hostedPaymentReturnOptions");
        $setting1->setSettingValue(
            json_encode([
                "url" => $payment_data['validation_url'],
                "cancelUrl" => $payment_data['validation_url'],
                "showReceipt" => true
            ])
        );

        $request = new AnetAPI\GetHostedPaymentPageRequest();
        $request->setMerchantAuthentication($merchantAuthentication);
        $request->setRefId($refId);
        $request->setTransactionRequest($transactionRequestType);

        $request->addToHostedPaymentSettings($setting1);

        $controller = new AnetController\GetHostedPaymentPageController($request);

        if ($authorizenet_test_mode) {
            $response = $controller->executeWithApiResponse(\net\authorize\api\constants\ANetEnvironment::SANDBOX);
        } else {
            $response = $controller->executeWithApiResponse(\net\authorize\api\constants\ANetEnvironment::PRODUCTION);
        }

        if (($response != null) && ($response->getMessages()->getResultCode() == "Ok")) {

            $token = $response->getToken();

            $payment_session_data = array();
            $payment_session_data["authorize_token"] = $token;

            $payment_session_data = json_encode($payment_session_data);
            DB::connect()->update('site_users_wallet', ['transaction_info' => $payment_session_data], ['wallet_transaction_id' => $payment_data['wallet_transaction_id']]);

            $auth_payment_url = Registry::load('config')->site_url.'topup_wallet/?embed_form='.urlencode($authorizenet_url).'&wallet_trans_id='.$payment_data['wallet_transaction_id'];

            $result['redirect'] = $auth_payment_url;
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
    $session_id = 'wallet_trans_'.$payment_data['validate_purchase'];

    $transaction_id = null;


    $wh_file = "fns/payments/authorizenet/webhooks/".$session_id.'.hk';

    $attempt = 0;

    while (!file_exists($wh_file) && $attempt < 4) {
        $attempt++;
        sleep(3);
    }


    if (file_exists($wh_file)) {
        $wh_data = file_get_contents($wh_file);
        $wh_data = json_decode($wh_data, true);

        if (isset($wh_data['payload']['id'])) {
            $transaction_id = $wh_data['payload']['id'];
        }
    }

    $result = array();
    $result['success'] = false;
    $result['transaction_info'] = $transaction_info;
    $result['error'] = 'something_went_wrong';

    if (!empty($transaction_id)) {

        try {

            $merchantAuthentication = new AnetAPI\MerchantAuthenticationType();
            $merchantAuthentication->setName($authorizenet_merchant_login_id);
            $merchantAuthentication->setTransactionKey($authorizenet_merchant_transaction_key);
            $request = new AnetAPI\GetTransactionDetailsRequest();
            $request->setMerchantAuthentication($merchantAuthentication);
            $request->setTransId($transaction_id);

            $controller = new AnetController\GetTransactionDetailsController($request);

            if ($authorizenet_test_mode) {
                $response = $controller->executeWithApiResponse(\net\authorize\api\constants\ANetEnvironment::SANDBOX);
            } else {
                $response = $controller->executeWithApiResponse(\net\authorize\api\constants\ANetEnvironment::PRODUCTION);
            }

            if (($response != null) && ($response->getMessages()->getResultCode() == "Ok")) {
                $transactionResponse = $response->getTransaction();


                if ($transactionResponse !== null && $transactionResponse->getResponseCode() == "1") {
                    $result = array();
                    $result['success'] = true;

                    if (file_exists($wh_file)) {
                        unlink($wh_file);
                    }
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