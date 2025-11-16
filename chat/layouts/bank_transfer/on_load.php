<?php

$domain_url_path = urldecode(Registry::load('config')->url_path);
$domain_url_path = preg_split('/\//', $domain_url_path);
$wallet_transaction_id = null;

if (isset($domain_url_path[1])) {
    $wallet_transaction_id = filter_var($domain_url_path[1], FILTER_SANITIZE_NUMBER_INT);
}

if (!empty($wallet_transaction_id)) {
    $columns = $join = $where = null;
    $columns = [
        "site_users_wallet.user_id",
        "site_users_wallet.transaction_info",
        "site_users_wallet.wallet_amount",
        "site_users_wallet.currency_code",
        "site_users_wallet.payment_gateway_id",
        "site_users_wallet.transaction_info",
        "site_users_wallet.transaction_status",
        'payment_gateways.identifier',
        'payment_gateways.credentials'
    ];
    $join["[>]payment_gateways"] = ['site_users_wallet.payment_gateway_id' => 'payment_gateway_id'];
    $where = [
        "site_users_wallet.wallet_transaction_id" => $wallet_transaction_id,
        "site_users_wallet.transaction_type" => 1,
        "site_users_wallet.transaction_status" => 0,
        "site_users_wallet.payment_gateway_id[!]" => null,
        "site_users_wallet.user_id" => Registry::load('current_user')->id
    ];

    $wallet_transaction = DB::connect()->select('site_users_wallet', $join, $columns, $where);

    if (isset($wallet_transaction[0])) {
        $wallet_transaction = $wallet_transaction[0];
    }

    if (isset($wallet_transaction['wallet_amount']) && isset($wallet_transaction['identifier']) && !empty($wallet_transaction['identifier'])) {

        if ((int) $wallet_transaction['transaction_status'] !== 0) {
            redirect(Registry::load('config')->site_url);
        }

        $columns = $join = $where = null;
        $columns = [
            'wallet_bank_receipts.receipt_status',
            'wallet_bank_receipts.receipt_file_name',
            'wallet_bank_receipts.bank_transfer_receipt_id'
        ];
        $where["wallet_bank_receipts.wallet_transaction_id"] = $wallet_transaction_id;
        $bank_receipt = DB::connect()->select('wallet_bank_receipts', $columns, $where);


    } else {
        $wallet_transaction_id = null;
    }
}

if (empty($wallet_transaction_id)) {
    $layout_variable = array();
    $layout_variable['title'] = $layout_variable['status'] = Registry::load('strings')->failed;
    $layout_variable['description'] = Registry::load('strings')->invalid_transaction;
    $layout_variable['button'] = Registry::load('strings')->continue_text;
    $layout_variable['successful'] = false;

    include_once 'layouts/transaction_status/layout.php';
    exit;
}