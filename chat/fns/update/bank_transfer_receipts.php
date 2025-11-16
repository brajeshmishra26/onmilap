<?php

$result = array();
$result['success'] = false;
$result['error_message'] = Registry::load('strings')->went_wrong;
$result['error_key'] = 'something_went_wrong';
$no_error = true;
$wallet_transaction_id = $bank_transfer_receipt_id = null;

if (role(['permissions' => ['bank_transfer_receipts' => 'validate']])) {

    if (isset($data["bank_transfer_receipt_id"])) {
        $bank_transfer_receipt_id = filter_var($data["bank_transfer_receipt_id"], FILTER_SANITIZE_NUMBER_INT);
    }

    if (!empty($bank_transfer_receipt_id)) {
        $columns = [
            'wallet_bank_receipts.wallet_transaction_id',
            'wallet_bank_receipts.fund_status',
            'site_users_wallet.wallet_amount',
            'site_users_wallet.user_id',
            'site_users_wallet.wallet_fund_status'
        ];
        $where["wallet_bank_receipts.bank_transfer_receipt_id"] = $bank_transfer_receipt_id;
        $where["LIMIT"] = 1;
        $join["[>]site_users_wallet"] = ["wallet_bank_receipts.wallet_transaction_id" => "wallet_transaction_id"];

        $bank_receipt = DB::connect()->select('wallet_bank_receipts', $join, $columns, $where);

        if (isset($bank_receipt[0])) {
            $wallet_transaction_id = $bank_receipt[0]['wallet_transaction_id'];
        }
    }

    if (!empty($wallet_transaction_id) && isset($data['take_action']) && !empty($data['take_action'])) {

        if ($data['take_action'] === 'disapprove') {
            DB::connect()->update('site_users_wallet', ['transaction_status' => 2, "updated_on" => Registry::load('current_user')->time_stamp], ['wallet_transaction_id' => $wallet_transaction_id]);
            DB::connect()->update('wallet_bank_receipts', ['receipt_status' => 2, "updated_on" => Registry::load('current_user')->time_stamp], ["wallet_bank_receipts.bank_transfer_receipt_id" => $bank_transfer_receipt_id]);
        } else if ($data['take_action'] === 'approve') {
            DB::connect()->update('site_users_wallet', ['transaction_status' => 1, "updated_on" => Registry::load('current_user')->time_stamp], ['wallet_transaction_id' => $wallet_transaction_id]);
            DB::connect()->update('wallet_bank_receipts', ['receipt_status' => 1, "updated_on" => Registry::load('current_user')->time_stamp], ["wallet_bank_receipts.bank_transfer_receipt_id" => $bank_transfer_receipt_id]);
        } else if ($data['take_action'] === 'approve_topup') {
            DB::connect()->update('site_users_wallet', ['transaction_status' => 1, 'wallet_fund_status' => 1, "updated_on" => Registry::load('current_user')->time_stamp], ['wallet_transaction_id' => $wallet_transaction_id]);
            DB::connect()->update('wallet_bank_receipts', ['receipt_status' => 1, 'fund_status' => 1, "updated_on" => Registry::load('current_user')->time_stamp], ["wallet_bank_receipts.bank_transfer_receipt_id" => $bank_transfer_receipt_id]);

            if ((int) $bank_receipt[0]['fund_status'] === 0 && (int) $bank_receipt[0]['wallet_fund_status'] === 0) {
                include_once 'fns/wallet/load.php';

                $wallet_data = [
                    'credit' => $bank_receipt[0]['wallet_amount'],
                    'user_id' => $bank_receipt[0]['user_id']
                ];
                UserWallet($wallet_data);
            }
        }
    }

    if ($no_error) {
        $result = array();
        $result['success'] = true;
        $result['todo'] = 'reload';
        $result['reload'] = ['transactions', 'bank_transfer_receipts'];
    }

}
?>