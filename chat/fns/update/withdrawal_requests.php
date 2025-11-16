<?php

include_once 'fns/wallet/load.php';

$result = array();
$result['success'] = false;
$result['error_message'] = Registry::load('strings')->went_wrong;
$result['error_key'] = 'something_went_wrong';
$no_error = true;
$withdrawal_request_id = null;


if (role(['permissions' => ['wallet' => 'manage_withdrawals']])) {
    if (isset($data["withdrawal_request_id"])) {
        $withdrawal_request_id = filter_var($data["withdrawal_request_id"], FILTER_SANITIZE_NUMBER_INT);
    }

    if (!empty($withdrawal_request_id)) {

        $columns = $where = null;
        $columns = [
            'withdrawal_requests.user_id',
            'withdrawal_requests.withdrawal_amount',
            'withdrawal_requests.currency_code',
            'withdrawal_requests.withdrawal_status',
        ];

        $where["withdrawal_requests.withdrawal_request_id"] = $withdrawal_request_id;

        $where["LIMIT"] = 1;

        $withdrawal_request = DB::connect()->select('withdrawal_requests', $columns, $where);

        if (isset($withdrawal_request[0])) {

            $withdrawal_request = $withdrawal_request[0];

            $update_data = array();

            if (isset($data['reference_note'])) {
                $update_data['reference_note'] = htmlspecialchars($data['reference_note'], ENT_QUOTES, 'UTF-8');
            }

            if (isset($data['take_action'])) {


                $withdraw_amount = (int)$withdrawal_request['withdrawal_amount'];
                $wallet_balance = DB::connect()->select('site_users', ['wallet_balance'], ['user_id' => $withdrawal_request['user_id'], 'LIMIT' => 1]);

                if (isset($wallet_balance[0])) {
                    $wallet_balance = (int)$wallet_balance[0]['wallet_balance'];
                } else {
                    $wallet_balance = 0;
                }

                if ($data['take_action'] === 'approve' && (int)$withdrawal_request['withdrawal_status'] !== 1) {

                    if ($withdraw_amount > $wallet_balance) {
                        if (Registry::load('settings')->currency_symbol_placement === 'right') {
                            $wallet_amount = ' ['.$wallet_balance.Registry::load('settings')->default_currency_symbol.']';
                        } else {
                            $wallet_amount = ' ['.Registry::load('settings')->default_currency_symbol.$wallet_balance.']';
                        }

                        $result['error_message'] = Registry::load('strings')->insufficient_wallet_balance_withdrawal.$wallet_amount;
                        $result['error_key'] = 'insufficient_wallet_balance_withdrawal';
                        $no_error = false;
                    } else {
                        $update_data['withdrawal_status'] = 1;
                        $log_transaction = ['order_type' => 'withdrawal'];
                        $log_transaction = json_encode($log_transaction);

                        $wallet_data = [
                            'debit' => $withdrawal_request['withdrawal_amount'],
                            'user_id' => $withdrawal_request['user_id'],
                            'log_transaction' => $log_transaction
                        ];
                        UserWallet($wallet_data);
                    }

                } else if ($data['take_action'] === 'disapprove' && (int)$withdrawal_request['withdrawal_status'] !== 2) {
                    $update_data['withdrawal_status'] = 2;

                    $log_transaction = ['order_type' => 'withdrawal_reversal'];
                    $log_transaction = json_encode($log_transaction);

                    if ((int)$withdrawal_request['withdrawal_status'] === 1) {
                        $wallet_data = [
                            'credit' => $withdrawal_request['withdrawal_amount'],
                            'user_id' => $withdrawal_request['user_id'],
                            'log_transaction' => $log_transaction
                        ];
                        UserWallet($wallet_data);
                    }
                }
            }

            if ($no_error && !empty($update_data)) {
                DB::connect()->update('withdrawal_requests', $update_data, ["withdrawal_requests.withdrawal_request_id" => $withdrawal_request_id]);
            }

            if ($no_error) {
                $result = array();
                $result['success'] = true;
                $result['todo'] = 'reload';
                $result['reload'] = ['withdrawal_requests', 'withdrawals'];
            }

        }
    }
}