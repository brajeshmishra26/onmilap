<?php

include_once "fns/currency_tools/load.php";

$page_content = [
    'title' => 'Currency Conversion in Progress',
    'loading_text' => 'Currency Conversion in Progress',
    'subtitle' => 'Please Wait',
    'redirect' => Registry::load('config')->site_url.'basic_process?process=currency_conversion'
];


$cache_filePath = 'assets/cache/currency_conversion.cache';
$balances = array();

if (file_exists($cache_filePath)) {
    $currency_data = file_get_contents($cache_filePath);
    $currency_data = json_decode($currency_data, true);

    if (!empty($currency_data) && isset($currency_data['current_user_id'])) {

        $columns = ['site_users.wallet_balance', 'site_users.user_id'];

        $where = [
            'site_users.wallet_balance[!]' => NULL,
            'site_users.wallet_balance[!]' => 0,
            'site_users.user_id[>]' => $currency_data['current_user_id'],
            'LIMIT' => 50
        ];

        $balances = DB::connect()->select('site_users', $columns, $where);
    }
}

if (!empty($balances)) {
    $currency_from = $currency_data['currency_from'];
    $currency_to = $currency_data['currency_to'];

    foreach ($balances as $balance) {
        $wallet_balance = currency_converter($balance['wallet_balance'], $currency_from, $currency_to);

        if (empty($wallet_balance)) {
            break;
        }

        $wallet_balance = intval(round($wallet_balance));
        $currency_data['current_user_id'] = $balance['user_id'];
        DB::connect()->update('site_users', ['wallet_balance' => $wallet_balance], ['user_id' => $balance['user_id']]);
    }

    $currency_data = json_encode($currency_data);
    file_put_contents($cache_filePath, $currency_data);

} else {
    $page_content = [
        'title' => 'Successfully Completed',
        'page_content' => 'Process Successfully Completed',
        'heading' => 'Yay!',
        'page_status' => 'success',
        'button_text' => 'Go to Homepage',
        'button_link' => Registry::load('config')->site_url
    ];

    if (file_exists($cache_filePath)) {
        unlink($cache_filePath);
    }
}
?>