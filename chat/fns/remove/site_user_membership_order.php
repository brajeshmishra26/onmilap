<?php


$result = array();
$noerror = true;

$result['success'] = false;
$result['error_message'] = Registry::load('strings')->something_went_wrong;
$result['error_key'] = 'something_went_wrong';

if (Registry::load('settings')->memberships === 'enable') {

    if (Registry::load('current_user')->logged_in) {
        $columns = $join = $where = null;
        $columns = [
            'site_users_membership.membership_package_id', 'site_users_membership.expiring_on',
            'site_users_membership.started_on', 'site_users_membership.non_expiring', 'membership_packages.site_role_id_on_expire',
            "membership_packages.cancellable", "membership_packages.refundable_on_cancel",
            'membership_packages.pricing', 'site_users_membership.package_amount', 'site_users_membership.currency_code'
        ];
        $where['site_users_membership.user_id'] = Registry::load('current_user')->id;
        $join = ["[>]membership_packages" => ["site_users_membership.membership_package_id" => "membership_package_id"]];

        $user_membership = DB::connect()->select('site_users_membership', $join, $columns, $where);

        if (isset($user_membership[0])) {
            $user_membership = $user_membership[0];

            if (!empty($user_membership['cancellable'])) {

                $site_role_id_on_expire = $user_membership['site_role_id_on_expire'];

                if (empty($site_role_id_on_expire)) {
                    $site_role_id_on_expire = Registry::load('site_role_attributes')->default_site_role;
                }

                DB::connect()->update('site_users', ['site_role_id' => $site_role_id_on_expire],
                    ['site_users.user_id' => Registry::load('current_user')->id]);

                DB::connect()->delete("site_users_membership", ["user_id" => Registry::load('current_user')->id]);

                if (!empty($user_membership['refundable_on_cancel'])) {

                    $refund_amount = $user_membership['pricing'];

                    if (!empty($user_membership['package_amount'])) {
                        if (!empty($user_membership['currency_code'])) {
                            if ($user_membership['currency_code'] !== Registry::load('settings')->default_currency) {
                                include_once "fns/currency_tools/load.php";
                                $currency_from = $user_membership['currency_code'];
                                $currency_to = Registry::load('settings')->default_currency;
                                $user_membership['package_amount'] = currency_converter($user_membership['package_amount'], $currency_from, $currency_to);
                            }
                        }
                        if (!empty($user_membership['package_amount'])) {
                            $refund_amount = $user_membership['package_amount'];
                        }
                    }

                    if (!empty($refund_amount)) {
                        if (empty($user_membership['non_expiring'])) {

                            $start_date = $user_membership['started_on'];
                            $expiry_date = $user_membership['expiring_on'];

                            $cancellation_date = date('Y-m-d H:i:s');

                            $start_timestamp = strtotime($start_date);
                            $expiry_timestamp = strtotime($expiry_date);
                            $cancellation_timestamp = strtotime($cancellation_date);

                            if ($cancellation_timestamp > $expiry_timestamp) {
                                $cancellation_timestamp = $expiry_timestamp;
                            }

                            $total_duration_seconds = $expiry_timestamp - $start_timestamp;

                            $used_duration_seconds = $cancellation_timestamp - $start_timestamp;

                            $remaining_duration_seconds = $total_duration_seconds - $used_duration_seconds;

                            $remaining_duration_seconds = max(0, $remaining_duration_seconds);
                            $refund_amount = ($remaining_duration_seconds / $total_duration_seconds) * $refund_amount;
                            $refund_amount = round($refund_amount, 2);
                            $refund_amount = number_format($refund_amount, 2);

                        }

                        if (!empty($refund_amount)) {
                            include_once 'fns/wallet/load.php';

                            $transaction_info = [
                                'order_type' => 'membership_refund',
                                'membership_package_id' => $user_membership['membership_package_id']
                            ];
                            $transaction_info = json_encode($transaction_info);

                            $wallet_data = [
                                'credit' => $refund_amount,
                                'user_id' => Registry::load('current_user')->id,
                                'log_transaction' => $transaction_info
                            ];
                            UserWallet($wallet_data);
                        }
                    }

                    $result = array();
                    $result['success'] = true;
                    $result['todo'] = 'refresh';
                }

            }
        }
    }
}
?>