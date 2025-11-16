<?php

include('fns/payments/paymentwall/paymentwall.php');

$pw_project_key = $pw_secret_key = $pw_widget_code = null;

if (isset($payment_data['credentials']) && !empty($payment_data['credentials'])) {

    $credentials = json_decode($payment_data['credentials']);

    if (!empty($credentials)) {
        if (isset($credentials->pw_project_key)) {
            $pw_project_key = $credentials->pw_project_key;
        }

        if (isset($credentials->pw_secret_key)) {
            $pw_secret_key = $credentials->pw_secret_key;
        }

        if (isset($credentials->pw_widget_code)) {
            $pw_widget_code = $credentials->pw_widget_code;
        }

    }

}

if (empty($pw_project_key) || empty($pw_secret_key) || empty($pw_widget_code)) {
    $result['error_message'] = "Invalid payment method credentials — Contact the webmaster";
    $result['error_key'] = 'invalid_payment_credentials';
    return;
}


if (isset($payment_data['purchase'])) {

    $currency = Registry::load('settings')->default_currency;

    $pw_currency_codes = [
        'AED', 'ALL', 'ARS', 'AUD', 'AZN', 'BAM', 'BDT', 'BGN', 'BHD', 'BOB', 'BRL',
        'BYN', 'CAD', 'CHF', 'CLP', 'CNY', 'COP', 'CRC', 'CZK', 'DKK', 'DOP', 'DZD', 'EGP', 'ETB', 'EUR', 'GBP',
        'GEL', 'GTQ', 'HKD', 'HNL', 'HRK', 'HUF', 'IDR', 'ILS', 'INR', 'IQD', 'IRR', 'JMD', 'JOD', 'JPY', 'KES', 'KRW',
        'KWD', 'LBP', 'LKR', 'LYD', 'MAD', 'MDL', 'MXN', 'MYR', 'NGN', 'NIO', 'NOK', 'NZD', 'OMR', 'PAB', 'PEN', 'PHP',
        'PKR', 'PLN', 'PYG', 'QAR', 'RON', 'RSD', 'RUB', 'SAR', 'SDG', 'SEK', 'SGD', 'SYP', 'THB', 'TND', 'TRY', 'TWD', 'UAH',
        'USD', 'UYU', 'VEF', 'VND', 'XAF', 'XOF', 'YER', 'ZAR'
    ];

    if (!in_array(Registry::load('settings')->default_currency, $pw_currency_codes)) {

        $currency = 'USD';

        include_once "fns/currency_tools/load.php";
        $payment_data['purchase'] = currency_converter($payment_data['purchase'], Registry::load('settings')->default_currency);

        if (empty($payment_data['purchase'])) {
            $result['error_message'] = "Currency conversion was unsuccessful.";
            $result['error_key'] = 'invalid_payment_credentials';
            return;
        }
    }


    $session_id = 'wallet_trans_'.$payment_data['wallet_transaction_id'];

    Paymentwall_Config::getInstance()->set(array(
        'api_type' => Paymentwall_Config::API_GOODS,
        'public_key' => $pw_project_key,
        'private_key' => $pw_secret_key
    ));

    $widget = new Paymentwall_Widget(
        Registry::load('current_user')->id,
        $pw_widget_code,
        array(
            new Paymentwall_Product(
                $session_id,
                $payment_data['purchase'],
                $currency,
                $payment_data['transaction_name'],
                Paymentwall_Product::TYPE_FIXED
            )
        ),
        array(
            'email' => Registry::load('current_user')->email_address,
            'ps' => 'all',
            'success_url' => $payment_data['validation_url'].'?transaction_id=$ref',
            'failure_url' => $payment_data['validation_url'].'?transaction_id=$ref',
        )
    );

    $embed_url = urlencode($widget->getUrl());
    $embed_url = Registry::load('config')->site_url.'topup_wallet/?embed_url='.$embed_url;

    $result['redirect'] = $embed_url;
    return;

} else if (isset($payment_data['validate_purchase'])) {

    $transaction_info = array_merge($_GET, $_POST);

    $result = array();
    $result['success'] = false;
    $result['transaction_info'] = $transaction_info;
    $result['error'] = 'something_went_wrong';
    $session_id = 'wallet_trans_'.$payment_data['validate_purchase'];


    $params = array(
        'key' => $pw_project_key,
        'uid' => Registry::load('current_user')->id,
        'sign_version' => 2
    );

    if (isset($_GET['transaction_id']) && !empty($_GET['transaction_id'])) {
        $params['ref'] = $_GET['transaction_id'];
    } else {
        $params['ag_external_id'] = $session_id;
    }

    Paymentwall_Config::getInstance()->set(array('private_key' => $pw_secret_key));
    $params['sign'] = (new Paymentwall_Signature_Widget())->calculate(
        $params,
        $params['sign_version']
    );

    $url = 'https://api.paymentwall.com/api/rest/payment/?'.http_build_query($params);
    $curl = curl_init($url);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
    $response = curl_exec($curl);

    if (!empty($response)) {
        $response = json_decode($response, true);
        if (!empty($response) && isset($response['risk'])) {
            if ($response['risk'] === 'approved') {
                $result = array();
                $result['success'] = true;
                $result['transaction_info'] = $transaction_info;
            }
        }

    }
}