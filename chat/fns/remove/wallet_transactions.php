<?php
$result = array();
$noerror = true;

$result['success'] = false;
$result['error_message'] = Registry::load('strings')->went_wrong;
$result['error_key'] = 'something_went_wrong';
$wallet_transaction_ids = array();

if (role(['permissions' => ['wallet' => 'delete_site_transactions']])) {

    if (isset($data['wallet_transaction_id'])) {
        if (!is_array($data['wallet_transaction_id'])) {
            $data["wallet_transaction_id"] = filter_var($data["wallet_transaction_id"], FILTER_SANITIZE_NUMBER_INT);
            $wallet_transaction_ids[] = $data["wallet_transaction_id"];
        } else {
            $wallet_transaction_ids = array_filter($data["wallet_transaction_id"], 'ctype_digit');
        }
    }

    if (isset($data['wallet_transaction_id']) && !empty($data['wallet_transaction_id'])) {

        DB::connect()->delete("site_users_wallet", ["wallet_transaction_id" => $wallet_transaction_ids]);

        if (!DB::connect()->error) {
            $result = array();
            $result['success'] = true;
            $result['todo'] = 'reload';
            $result['reload'] = ['site_wallet_transactions', 'wallet_transactions'];
        } else {
            $result['errormsg'] = Registry::load('strings')->went_wrong;
        }
    }
}
?>