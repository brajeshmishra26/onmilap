<?php

if (isset($payment_data['purchase']) || isset($payment_data['validate_purchase'])) {

    if (isset($payment_data['validate_purchase'])) {
        $payment_data['wallet_transaction_id'] = $payment_data['validate_purchase'];
    }

    $bank_transfer_url = Registry::load('config')->site_url.'bank_transfer/'.$payment_data['wallet_transaction_id'].'/';

    $result['redirect'] = $bank_transfer_url;
    return;
}