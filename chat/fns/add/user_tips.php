<?php

include_once 'fns/wallet/load.php';

$result = array();
$result['success'] = false;
$result['error_message'] = Registry::load('strings')->went_wrong;
$result['error_key'] = 'something_went_wrong';

$tip_user_id = $tip_amount = 0;

$current_user_id = Registry::load('current_user')->id;

if (Registry::load('settings')->tips_system === 'enable') {
    if (role(['permissions' => ['tips' => 'send_tips']])) {

        if (isset($data['tip_user_id'])) {
            $data['tip_user_id'] = filter_var($data['tip_user_id'], FILTER_SANITIZE_NUMBER_INT);

            if (!empty($data['tip_user_id'])) {
                $tip_user_id = $data['tip_user_id'];

                if ((int)$tip_user_id === (int)$current_user_id) {
                    $tip_user_id = 0;
                }
            }
        }


        if (!empty($tip_user_id)) {

            $site_role_id = DB::connect()->select('site_users', ['site_role_id'], ['user_id' => $tip_user_id, 'LIMIT' => 1]);

            if (isset($site_role_id[0])) {
                $site_role_id = $site_role_id[0];
            } else {
                $site_role_id = 0;
            }

            if (empty($site_role_id) || !role(['permissions' => ['tips' => 'recieve_tips'], 'site_role_id' => $site_role_id])) {
                $tip_user_id = 0;
            }
        }

        if (isset($data['tip_amount']) && !empty($data['tip_amount'])) {
            $data['tip_amount'] = trim($data['tip_amount']);
            $data['tip_amount'] = preg_replace('/[^0-9.]/', '', $data['tip_amount']);

            if (!is_numeric($data['tip_amount'])) {
                $data['tip_amount'] = null;
            } else {
                $data['tip_amount'] = intval(ceil($data['tip_amount']));
                if ($data['tip_amount'] <= 0) {
                    $data['tip_amount'] = null;
                }
            }
        }

        if (!isset($data['tip_amount']) || empty($data['tip_amount'])) {
            $result['error_message'] = Registry::load('strings')->invalid_amount;
            $result['error_key'] = 'invalid_amount';
            $tip_user_id = 0;
        } else {
            $tip_amount = (int)$data['tip_amount'];
            $wallet_balance = DB::connect()->select('site_users', ['wallet_balance'], ['user_id' => $current_user_id, 'LIMIT' => 1]);

            if (isset($wallet_balance[0])) {
                $wallet_balance = (int)$wallet_balance[0]['wallet_balance'];
            } else {
                $wallet_balance = 0;
            }

            if ($tip_amount > $wallet_balance) {
                if (Registry::load('settings')->currency_symbol_placement === 'right') {
                    $wallet_amount = ' ['.$wallet_balance.Registry::load('settings')->default_currency_symbol.']';
                } else {

                    $wallet_amount = ' ['.Registry::load('settings')->default_currency_symbol.$wallet_balance.']';
                }
                $result['error_message'] = Registry::load('strings')->insufficient_wallet_balance.$wallet_amount;
                $result['error_key'] = 'insufficient_wallet_balance';
                $tip_user_id = 0;
            }
        }

        if (!empty($tip_user_id) && !empty($tip_amount)) {

            $log_transaction = ['order_type' => 'sent_tip', 'sent_to' => $tip_user_id];
            $log_transaction = json_encode($log_transaction);
            $wallet_data = [
                'debit' => $tip_amount,
                'user_id' => $current_user_id,
                'log_transaction' => $log_transaction
            ];
            UserWallet($wallet_data);

            $log_transaction = ['order_type' => 'received_tip', 'received_from' => $current_user_id];
            $log_transaction = json_encode($log_transaction);
            $wallet_data = [
                'credit' => $tip_amount,
                'user_id' => $tip_user_id,
                'log_transaction' => $log_transaction
            ];
            UserWallet($wallet_data);

            $tip_message = null;

            if (isset($data['tip_message']) && !empty($data['tip_message'])) {
                $tip_message = htmlspecialchars(trim($data['tip_message']), ENT_QUOTES, 'UTF-8');
            }

            DB::connect()->insert("site_user_tips", [
                "received_user_id" => $tip_user_id,
                "sent_user_id" => $current_user_id,
                "currency_code" => Registry::load('settings')->default_currency,
                "tip_amount" => $tip_amount,
                "message" => $tip_message,
                "created_on" => Registry::load('current_user')->time_stamp,
                "updated_on" => Registry::load('current_user')->time_stamp,
            ]);

            DB::connect()->insert("site_notifications", [
                "user_id" => $tip_user_id,
                "notification_type" => 'received_tip',
                "related_user_id" => $current_user_id,
                "created_on" => Registry::load('current_user')->time_stamp,
                "updated_on" => Registry::load('current_user')->time_stamp,
            ]);

            ws_push(['update' => 'site_notification', 'user_id' => $tip_user_id]);

            $result = array();
            $result['success'] = true;
            $result['force_reload_aside'] = 'tips_history';
            $result['close_modal'] = '#send_tips_modal';
        }

    }
}