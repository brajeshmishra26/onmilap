<?php
function UserWallet($data) {
    if (isset($data['user_id'])) {

        $data['user_id'] = filter_var($data["user_id"], FILTER_SANITIZE_NUMBER_INT);

        if (!empty($data['user_id'])) {

            $wallet_balance = DB::connect()->select('site_users', ['wallet_balance'], ['user_id' => $data['user_id'], 'LIMIT' => 1]);

            if (isset($wallet_balance[0])) {
                $wallet_balance = $wallet_balance[0]['wallet_balance'];
            } else {
                $wallet_balance = 0;
            }

            if (isset($data['credit'])) {
                $data['credit'] = preg_replace('/[^0-9.]/', '', $data["credit"]);
                $data['credit'] = preg_replace('/\.(?=.*\.)/', '', $data['credit']);
                $amount = floatval($data['credit']);
                $wallet_balance = floatval($wallet_balance) + $amount;
                $transaction_type = 1;

            } else if (isset($data['debit'])) {
                $data['debit'] = preg_replace('/[^0-9.]/', '', $data["debit"]);
                $data['debit'] = preg_replace('/\.(?=.*\.)/', '', $data['debit']);
                $amount = floatval($data['debit']);
                $transaction_type = 2;

                $wallet_balance = floatval($wallet_balance) - $amount;
            }

            if (isset($data['credit']) || isset($data['debit'])) {

                if (isset($data['log_transaction'])) {
                    DB::connect()->insert("site_users_wallet", [
                        "user_id" => $data['user_id'],
                        'wallet_fund_status' => 1,
                        "wallet_amount" => $amount,
                        "currency_code" => Registry::load('settings')->default_currency,
                        "transaction_type" => $transaction_type,
                        "transaction_status" => 1,
                        "transaction_info" => $data['log_transaction'],
                        "created_on" => Registry::load('current_user')->time_stamp,
                        "updated_on" => Registry::load('current_user')->time_stamp,
                    ]);
                }

                DB::connect()->update('site_users', ['wallet_balance' => $wallet_balance], ['user_id' => $data['user_id']]);
            }

        }
    }
}