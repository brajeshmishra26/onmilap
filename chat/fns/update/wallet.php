<?php

include_once 'fns/payments/load.php';

$result = array();
$result['success'] = false;
$result['error_message'] = Registry::load('strings')->went_wrong;
$result['error_key'] = 'something_went_wrong';
$noerror = true;
$user_id = Registry::load('current_user')->id;

if (isset($data['withdrawal'])) {
    if (Registry::load('settings')->allow_user_withdrawals === 'yes' && role(['permissions' => ['wallet' => 'withdraw_money']])) {
        if (isset($data['withdraw_amount']) && !empty($data['withdraw_amount'])) {
            $data['withdraw_amount'] = trim($data['withdraw_amount']);
            $data['withdraw_amount'] = preg_replace('/[^0-9.]/', '', $data['withdraw_amount']);

            if (!is_numeric($data['withdraw_amount'])) {
                $data['withdraw_amount'] = null;
            } else {
                $data['withdraw_amount'] = intval(ceil($data['withdraw_amount']));
                if ($data['withdraw_amount'] <= 0) {
                    $data['withdraw_amount'] = null;
                }
            }
        }

        if (!isset($data['transfer_details']) || empty($data['transfer_details'])) {
            $result['error_message'] = Registry::load('strings')->requires_bank_details;
            $result['error_key'] = 'requires_bank_details';
            $noerror = false;
        }

        if (!isset($data['withdraw_amount']) || empty($data['withdraw_amount'])) {
            $result['error_message'] = Registry::load('strings')->invalid_withdrawal_amount;
            $result['error_key'] = 'invalid_withdrawal_amount';
            $noerror = false;
        }
        if ($noerror) {
            $min_withdrawal_amount = (int)Registry::load('settings')->min_withdrawal_amount;
            $max_withdrawal_amount = (int)Registry::load('settings')->max_withdrawal_amount;
            $withdraw_amount = (int)$data['withdraw_amount'];

            $wallet_balance = DB::connect()->select('site_users', ['wallet_balance'], ['user_id' => $user_id, 'LIMIT' => 1]);

            $pending_requests = DB::connect()->count('withdrawal_requests', ['user_id' => $user_id, 'withdrawal_status' => 0]);

            if (isset($wallet_balance[0])) {
                $wallet_balance = (int)$wallet_balance[0]['wallet_balance'];
            } else {
                $wallet_balance = 0;
            }

            if ((int)$pending_requests > 0) {
                $result['error_message'] = Registry::load('strings')->withdrawal_request_pending_review;
                $result['error_key'] = 'withdrawal_request_pending_review';
                $noerror = false;
            } else if ($withdraw_amount > $wallet_balance) {

                if (Registry::load('settings')->currency_symbol_placement === 'right') {
                    $wallet_amount = ' ['.$wallet_balance.Registry::load('settings')->default_currency_symbol.']';
                } else {
                    $wallet_amount = ' ['.Registry::load('settings')->default_currency_symbol.$wallet_balance.']';
                }

                $result['error_message'] = Registry::load('strings')->insufficient_wallet_balance_withdrawal.$wallet_amount;
                $result['error_key'] = 'insufficient_wallet_balance_withdrawal';
                $noerror = false;
            } else if ($withdraw_amount > $max_withdrawal_amount) {
                $result['error_message'] = Registry::load('strings')->exceeded_max_withdrawal_amount.' ['.Registry::load('settings')->default_currency_symbol.$max_withdrawal_amount.']';
                $result['error_key'] = 'exceeded_max_withdrawal_amount';
                $noerror = false;
            } else if ($withdraw_amount < $min_withdrawal_amount) {
                $result['error_message'] = Registry::load('strings')->requires_min_withdrawal_amount.' ['.Registry::load('settings')->default_currency_symbol.$min_withdrawal_amount.']';
                $result['error_key'] = 'requires_min_withdrawal_amount';
                $noerror = false;
            }

            if ($noerror) {
                $data['transfer_details'] = htmlspecialchars($data['transfer_details'], ENT_QUOTES, 'UTF-8');

                DB::connect()->insert("withdrawal_requests", [
                    "user_id" => $user_id,
                    "transfer_details" => $data['transfer_details'],
                    "currency_code" => Registry::load('settings')->default_currency,
                    "withdrawal_amount" => $withdraw_amount,
                    "created_on" => Registry::load('current_user')->time_stamp,
                    "updated_on" => Registry::load('current_user')->time_stamp,
                ]);

                $result['success_message'] = Registry::load('strings')->withdrawal_request_submitted;
                $result['clear_form'] = true;
            }
        }
    }

} else if (role(['permissions' => ['wallet' => 'topup_wallet']])) {
    if (isset($data['amount']) && !empty($data['amount'])) {
        $data['amount'] = trim($data['amount']);
        $data['amount'] = preg_replace('/[^0-9.]/', '', $data['amount']);

        if (!is_numeric($data['amount'])) {
            $data['amount'] = null;
        } else {
            $data['amount'] = intval(ceil($data['amount']));
            if ($data['amount'] <= 0) {
                $data['amount'] = null;
            }
        }
    }

    if (isset($data['payment_gateway_id'])) {

        $columns = $join = $where = null;
        $columns = ['payment_gateways.payment_gateway_id', 'payment_gateways.identifier', 'payment_gateways.credentials'];
        $where["payment_gateways.payment_gateway_id"] = $data['payment_gateway_id'];
        $where["payment_gateways.disabled[!]"] = 1;
        $gateway = DB::connect()->select('payment_gateways', $columns, $where);

        if (isset($gateway[0])) {
            $data['payment_gateway_id'] = $gateway[0]['payment_gateway_id'];
        } else {
            $data['payment_gateway_id'] = null;
        }
    }


    if (!isset($data['amount']) || empty($data['amount'])) {
        $result['error_message'] = Registry::load('strings')->invalid_topup_amount;
        $result['error_key'] = 'invalid_topup_amount';
        $noerror = false;
    }

    if (!isset($data['payment_gateway_id']) || empty($data['payment_gateway_id'])) {
        $result['error_message'] = Registry::load('strings')->invalid_payment_method;
        $result['error_key'] = 'invalid_payment_method';
        $noerror = false;
    }



    if (Registry::load('settings')->require_billing_address === 'yes') {
        $columns = $join = $where = null;
        $columns = ['billed_to', 'street_address', 'city', 'state', 'country', 'postal_code'];
        $where["billing_address.user_id"] = Registry::load('current_user')->id;
        $billing_address = DB::connect()->select('billing_address', $columns, $where);

        if (!empty($billing_address)) {
            if (empty($billing_address[0]['billed_to'])) {
                $billing_address = null;
            }
        }

        if (empty($billing_address)) {
            $result['error_message'] = Registry::load('strings')->billing_address_not_found;
            $result['error_key'] = 'invalid_payment_method';
            $noerror = false;
        }
    }


    if ($noerror) {
        DB::connect()->insert("site_users_wallet", [
            "user_id" => Registry::load('current_user')->id,
            "wallet_amount" => $data['amount'],
            "currency_code" => Registry::load('settings')->default_currency,
            "payment_gateway_id" => $data['payment_gateway_id'],
            "transaction_type" => 1,
            "created_on" => Registry::load('current_user')->time_stamp,
            "updated_on" => Registry::load('current_user')->time_stamp,
        ]);

        if (!DB::connect()->error) {
            $wallet_transaction_id = DB::connect()->id();
            $validation_url = Registry::load('config')->site_url.'topup_wallet/'.$wallet_transaction_id.'/';

            $payment_data = [
                'gateway' => $gateway[0]['identifier'],
                'gateway_id' => $gateway[0]['payment_gateway_id'],
                'wallet_transaction_id' => $wallet_transaction_id,
                'purchase' => $data['amount'],
                'transaction_name' => 'TopUp Wallet ['.$wallet_transaction_id.']',
                'credentials' => $gateway[0]['credentials'],
                'description' => 'TopUp Wallet - '.$data['amount'].' ['.Registry::load('settings')->site_name.']',
                'validation_url' => $validation_url,
            ];

            $result = payment_module($payment_data);

            if (isset($result['error_key']) && $result['error_key'] === 'invalid_payment_credentials') {
                DB::connect()->delete("site_users_wallet", [
                    "wallet_transaction_id" => $wallet_transaction_id,
                ]);
            }
        }
    }
}

?>