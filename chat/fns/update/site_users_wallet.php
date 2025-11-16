<?php

$result = array();
$result['success'] = false;
$result['error_message'] = Registry::load('strings')->went_wrong;
$result['error_key'] = 'something_went_wrong';
$no_error = true;
$user_id = null;

if (role(['permissions' => ['wallet' => 'manage_user_wallet_funds']])) {

    if (isset($data["user_id"])) {
        $user_id = filter_var($data["user_id"], FILTER_SANITIZE_NUMBER_INT);
    }

    $required_fields = ['take_action', 'amount'];

    $result = array();
    $result['success'] = false;
    $result['error_message'] = Registry::load('strings')->invalid_value;
    $result['error_key'] = 'invalid_value';

    if (isset($data['amount'])) {
        $data['amount'] = (float)$data['amount'];
    }

    foreach ($required_fields as $required_field) {
        if (!isset($data[$required_field]) || empty($data[$required_field])) {
            $result['error_variables'][] = [$required_field];
            $no_error = false;
        }
    }

    if (!empty($user_id) && $no_error) {
        $columns = $join = $where = null;
        $columns = ['site_roles.site_role_attribute', 'site_roles.role_hierarchy'];

        $join["[>]site_roles"] = ["site_users.site_role_id" => "site_role_id"];

        if (Registry::load('current_user')->site_role_attribute !== 'administrators') {
            $where['site_roles.role_hierarchy[<]'] = Registry::load('current_user')->role_hierarchy;
        }

        $where["site_users.user_id"] = $user_id;
        $where["LIMIT"] = 1;
        $user_info = DB::connect()->select('site_users', $join, $columns, $where);

        if (!isset($user_info[0])) {
            $user_id = null;
            $no_error = false;
            $result['error_message'] = Registry::load('strings')->permission_denied;
            $result['error_key'] = 'permission_denied';
        }
    }

    if (!empty($user_id) && $no_error) {

        $amount = $data['amount'];

        include_once 'fns/wallet/load.php';

        if ($data['take_action'] === 'credit_funds') {

            $transaction_info = ['order_type' => 'funds_credited', 'action_by' => Registry::load('current_user')->id];
            $transaction_info = json_encode($transaction_info);

            $wallet_data = [
                'credit' => $amount,
                'user_id' => $user_id,
                'log_transaction' => $transaction_info
            ];
            UserWallet($wallet_data);
        } else {

            $transaction_info = ['order_type' => 'funds_debited', 'action_by' => Registry::load('current_user')->id];
            $transaction_info = json_encode($transaction_info);

            $wallet_data = [
                'debit' => $amount,
                'user_id' => $user_id,
                'log_transaction' => $transaction_info
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
?>