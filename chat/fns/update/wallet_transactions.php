<?php

include_once 'fns/wallet/load.php';

$result = array();
$result['success'] = false;
$result['error_message'] = Registry::load('strings')->went_wrong;
$result['error_key'] = 'something_went_wrong';
$no_error = true;
$wallet_transaction_id = null;
$wallet_transaction = array();

if (role(['permissions' => ['wallet' => 'edit_site_transactions']])) {

    if (isset($data["wallet_transaction_id"])) {
        $wallet_transaction_id = filter_var($data["wallet_transaction_id"], FILTER_SANITIZE_NUMBER_INT);
    }

    if (!empty($wallet_transaction_id)) {
        $columns = [
            'site_users_wallet.wallet_transaction_id', 'site_users_wallet.user_id',
            'site_users_wallet.wallet_amount',
            'site_users_wallet.currency_code',
            'site_users_wallet.transaction_type',
            'site_users_wallet.transaction_status',
            'payment_gateways.identifier',
            'site_users_wallet.wallet_fund_status',
        ];
        $join["[>]payment_gateways"] = ['site_users_wallet.payment_gateway_id' => 'payment_gateway_id'];


        $where["site_users_wallet.wallet_transaction_id"] = $wallet_transaction_id;

        $where["LIMIT"] = 1;

        $wallet_transaction = DB::connect()->select('site_users_wallet', $join, $columns, $where);

        if (isset($wallet_transaction[0])) {
            $wallet_transaction = $wallet_transaction[0];
            $wallet_transaction_id = $wallet_transaction['wallet_transaction_id'];
        }
        if (!empty($wallet_transaction) && isset($data['take_action']) && !empty($data['take_action'])) {

            if ($data['take_action'] === 'disapprove') {
                DB::connect()->update('site_users_wallet', ['transaction_status' => 2, "updated_on" => Registry::load('current_user')->time_stamp], ['wallet_transaction_id' => $wallet_transaction_id]);
            } else if ($data['take_action'] === 'approve') {
                DB::connect()->update('site_users_wallet', ['transaction_status' => 1, "updated_on" => Registry::load('current_user')->time_stamp], ['wallet_transaction_id' => $wallet_transaction_id]);
            } else if ($data['take_action'] === 'disapprove_debit' && (int) $wallet_transaction['wallet_fund_status'] === 1) {

                DB::connect()->update('site_users_wallet', ['transaction_status' => 2, 'wallet_fund_status' => 0, "updated_on" => Registry::load('current_user')->time_stamp], ['wallet_transaction_id' => $wallet_transaction_id]);

                $wallet_data = [
                    'debit' => $wallet_transaction['wallet_amount'],
                    'user_id' => $wallet_transaction['user_id']
                ];
                UserWallet($wallet_data);

            } else if ($data['take_action'] === 'approve_topup' && (int) $wallet_transaction['wallet_fund_status'] === 0) {

                DB::connect()->update('site_users_wallet', ['transaction_status' => 1, 'wallet_fund_status' => 1, "updated_on" => Registry::load('current_user')->time_stamp], ['wallet_transaction_id' => $wallet_transaction_id]);

                $wallet_data = [
                    'credit' => $wallet_transaction['wallet_amount'],
                    'user_id' => $wallet_transaction['user_id']
                ];
                UserWallet($wallet_data);

            } else if ($data['take_action'] === 'approve_debit' && (int) $wallet_transaction['wallet_fund_status'] === 0) {

                DB::connect()->update('site_users_wallet', ['transaction_status' => 1, 'wallet_fund_status' => 1, "updated_on" => Registry::load('current_user')->time_stamp], ['wallet_transaction_id' => $wallet_transaction_id]);

                $wallet_data = [
                    'debit' => $wallet_transaction['wallet_amount'],
                    'user_id' => $wallet_transaction['user_id']
                ];
                UserWallet($wallet_data);

            } else if ($data['take_action'] === 'disapprove_credit' && (int) $wallet_transaction['wallet_fund_status'] === 1) {

                DB::connect()->update('site_users_wallet', ['transaction_status' => 2, 'wallet_fund_status' => 0, "updated_on" => Registry::load('current_user')->time_stamp], ['wallet_transaction_id' => $wallet_transaction_id]);

                $wallet_data = [
                    'credit' => $wallet_transaction['wallet_amount'],
                    'user_id' => $wallet_transaction['user_id']
                ];
                UserWallet($wallet_data);

            }
        }

        if ($no_error) {
            $result = array();
            $result['success'] = true;
            $result['todo'] = 'reload';
            $result['reload'] = ['wallet_transactions', 'site_wallet_transactions'];
        }

    }

}
?>