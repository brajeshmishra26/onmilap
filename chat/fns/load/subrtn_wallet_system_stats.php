<?php


$index = 1;

$output = array();

$child_index = 0;
$output['module'][$index] = new stdClass();
$output['module'][$index]->type = 'numbers';

$items = array();


$wallet_balance = DB::connect()->sum('site_users', 'wallet_balance');

if (empty($wallet_balance)) {
    $wallet_balance = 0;
}

$items[$child_index]['title'] = Registry::load('strings')->gross_balance;
if (Registry::load('settings')->currency_symbol_placement === 'right') {
    $items[$child_index]['result'] = $wallet_balance. ' ' . Registry::load('settings')->default_currency_symbol;
} else {
    $items[$child_index]['result'] = Registry::load('settings')->default_currency_symbol . ' ' . $wallet_balance;
}

$items[$child_index]['attributes'] = [
    'class' => 'ltr_direction'
];


$child_index++;

$last_credit = DB::connect()->select(
    'site_users_wallet',
    ['wallet_amount', 'currency_code'],
    ['transaction_type' => 1, 'transaction_status' => 1, 'ORDER' => ['wallet_transaction_id' => "DESC"], 'LIMIT' => 1]
);

if (isset($last_credit[0])) {
    $last_credit = $last_credit[0]['currency_code'] . ' ' . $last_credit[0]['wallet_amount'];
} else {
    $last_credit = 0.00;
}

$items[$child_index]['title'] = Registry::load('strings')->wallet_last_credit;
$items[$child_index]['result'] = $last_credit;

$items[$child_index]['attributes'] = [
    'class' => 'load_aside',
    'load' => 'site_wallet_transactions',
    'role' => 'button'
];

$child_index++;


$last_debit = DB::connect()->select(
    'site_users_wallet',
    ['wallet_amount', 'currency_code'],
    ['transaction_type' => 2, 'transaction_status' => 1, 'ORDER' => ['wallet_transaction_id' => "DESC"], 'LIMIT' => 1]
);

if (isset($last_debit[0])) {
    $last_debit = $last_debit[0]['currency_code'] . ' ' . $last_debit[0]['wallet_amount'];
} else {
    $last_debit = 0.00;
}

$items[$child_index]['title'] = Registry::load('strings')->wallet_last_debit;
$items[$child_index]['result'] = $last_debit;

$items[$child_index]['attributes'] = [
    'class' => 'load_aside',
    'load' => 'site_wallet_transactions',
    'role' => 'button'
];

$child_index++;

$output['module'][$index]->items = $items;

$index++;


$output['module'][$index] = new stdClass();
$output['module'][$index]->title = Registry::load('strings')->last_transactions;
$output['module'][$index]->type = 'list';

$child_index = 0;
$items = array();

$columns = $where = $join = null;
$columns = [
    'site_users_wallet.wallet_transaction_id',
    'site_users_wallet.wallet_amount',
    'site_users_wallet.currency_code',
    'site_users_wallet.transaction_type',
    'site_users_wallet.transaction_info',
    'site_users_wallet.transaction_status',
    'site_users_wallet.created_on', 'site_users.display_name', 'site_users.username',
    'payment_gateways.identifier'
];

$join["[>]payment_gateways"] = ["site_users_wallet.payment_gateway_id" => "payment_gateway_id"];
$join["[>]site_users"] = ["site_users_wallet.user_id" => "user_id"];

$where["ORDER"] = ["site_users_wallet.wallet_transaction_id" => "DESC"];
$where["LIMIT"] = 15;

$last_transactions = DB::connect()->select('site_users_wallet', $join, $columns, $where);


foreach ($last_transactions as $transaction) {

    $transaction_symbol = Registry::load('config')->site_url . 'assets/files/defaults/debit_symbol.png';
    $transaction_status = Registry::load('strings')->debit;

    $color_scheme = 'light';

    if (Registry::load('current_user')->color_scheme == 'dark_mode') {
        $color_scheme = 'dark';
    }

    $payment_gateway_img = Registry::load('config')->site_url . 'assets/files/payment_gateways/' . $color_scheme . '/wallet.png';

    if (!empty($transaction['identifier'])) {
        $payment_gateway_img = Registry::load('config')->site_url;
        $payment_gateway_img = $payment_gateway_img . 'assets/files/payment_gateways/' . $color_scheme . '/' . $transaction['identifier'] . '.png';
    }

    if ((int) $transaction['transaction_type'] === 1) {
        $transaction_symbol = Registry::load('config')->site_url . 'assets/files/defaults/credit_symbol.png';
        $transaction_status = Registry::load('strings')->credit;
    }

    $created_on['date'] = $transaction['created_on'];
    $created_on['auto_format'] = true;
    $created_on['include_time'] = true;
    $created_on['timezone'] = Registry::load('current_user')->time_zone;
    $created_on = get_date($created_on);

    $items[$child_index] = new stdClass();
    $items[$child_index]->items[1]['type'] = 'image';
    $items[$child_index]->items[1]['class_name'] = 'small_size_img';
    $items[$child_index]->items[1]['image'] = $transaction_symbol;

    $items[$child_index]->items[2]['type'] = 'info';
    $items[$child_index]->items[2]['bold_text'] = $transaction['currency_code'] . ' ' . $transaction['wallet_amount'];
    $items[$child_index]->items[2]['text'] = $transaction['display_name'];

    $items[$child_index]->items[3]['type'] = 'info';

    $items[$child_index]->items[3]['bold_text'] = $created_on['date'];
    $items[$child_index]->items[3]['text'] = $created_on['time'];

    if (!empty($transaction['transaction_info'])) {
        $transaction_info = json_decode($transaction['transaction_info'], true);

        if (!empty($transaction_info) && isset($transaction_info['order_type'])) {
            $order_type = $transaction_info['order_type'];
            $items[$child_index]->items[3]['bold_text'] = Registry::load('strings')->$order_type;
            $items[$child_index]->items[3]['text'] = $created_on['date'];
        }
    }


    $items[$child_index]->items[5]['type'] = 'image';
    $items[$child_index]->items[5]['class_name'] = 'auto_size_img';
    $items[$child_index]->items[5]['image'] = $payment_gateway_img;

    $items[$child_index]->items[6]['type'] = 'button';

    $items[$child_index]->items[6]['class_name'] = 'pending';
    $items[$child_index]->items[6]['text'] = Registry::load('strings')->pending;

    if ((int) $transaction['transaction_status'] === 1) {
        $items[$child_index]->items[6]['class_name'] = 'success';
        $items[$child_index]->items[6]['text'] = Registry::load('strings')->success;
    } else if ((int) $transaction['transaction_status'] === 2) {
        $items[$child_index]->items[6]['class_name'] = 'failed';
        $items[$child_index]->items[6]['text'] = Registry::load('strings')->failed;
    }


    $child_index++;
}

$output['module'][$index]->items = $items;
$index++;

?>