<?php

$result = array();
$result['success'] = false;
$result['alert'] = Registry::load('strings')->went_wrong;

$user_id = Registry::load('current_user')->id;

if (isset($private_data['set_user_id']) && $private_data['set_user_id']) {
    $user_id = $private_data['set_user_id'];
}

if ($force_request || Registry::load('settings')->memberships === 'enable') {
    if ($force_request || role(['permissions' => ['memberships' => 'enroll_membership']])) {


        if (isset($data['membership_package_id']) && !empty($data['membership_package_id'])) {

            $columns = $join = $where = null;
            $columns = [
                'membership_packages.membership_package_id', 'membership_packages.pricing', 'membership_packages.related_site_role_id',
                'membership_packages.is_recurring', 'membership_packages.duration', 'membership_packages.site_role_id_on_expire',
                'membership_packages.role_restricted_package', 'membership_packages.duration_in_minutes'
            ];
            $where["membership_packages.membership_package_id"] = $data['membership_package_id'];
            $where["membership_packages.disabled[!]"] = 1;
            $package = DB::connect()->select('membership_packages', $columns, $where);

            $free_package = false;
            $place_order = true;

            if (Registry::load('current_user')->site_role_attribute === 'administrators') {
                $place_order = false;
                $result = array();
                $result['success'] = false;
                $result['alert'] = Registry::load('strings')->administrators_package_restrict;
                return;
            }

            if (isset($package[0])) {

                $package = $package[0];

                if (empty($package['pricing'])) {
                    $free_package = true;
                }

                if (!empty($package['role_restricted_package'])) {
                    $check_restricted_roles = DB::connect()->count('membership_packages_roles', [
                        'membership_package_id' => $data["membership_package_id"],
                        'site_role_id' => Registry::load('current_user')->site_role,
                    ]);

                    if (empty($check_restricted_roles)) {
                        $place_order = false;
                        $result = array();
                        $result['success'] = false;
                        $result['alert'] = Registry::load('strings')->package_not_eligible;
                        return;
                    }
                }


                if (!$free_package) {

                    if (isset($private_data['package_amount'])) {
                        $private_data['package_amount'] = (float)$private_data['package_amount'];
                    }

                    if (isset($private_data['package_amount']) && !empty($private_data['package_amount'])) {

                        if (isset($private_data['currency_code']) && !empty($private_data['currency_code'])) {
                            if ($private_data['currency_code'] !== Registry::load('settings')->default_currency) {
                                include_once "fns/currency_tools/load.php";
                                $currency_from = $private_data['currency_code'];
                                $currency_to = Registry::load('settings')->default_currency;
                                $private_data['package_amount'] = currency_converter($private_data['package_amount'], $currency_from, $currency_to);
                            }
                        }
                        if (!empty($private_data['package_amount'])) {
                            $package['pricing'] = $private_data['package_amount'];
                        }

                    }


                    $wallet_balance = DB::connect()->select('site_users', ['wallet_balance'], ['user_id' => $user_id, 'LIMIT' => 1]);

                    if (isset($wallet_balance[0])) {
                        $wallet_balance = $wallet_balance[0]['wallet_balance'];
                    } else {
                        $wallet_balance = 0;
                    }

                    if ((float)$package['pricing'] > (float)$wallet_balance) {
                        $place_order = false;
                        $result = array();
                        $result['success'] = false;
                        $result['alert'] = Registry::load('strings')->insufficient_wallet_balance;
                    }


                }

                if (!$force_request && $place_order) {
                    $user_membership = DB::connect()->select('site_users_membership',
                        ['site_users_membership.membership_info_id'],
                        ['site_users_membership.user_id' => $user_id,
                            'site_users_membership.expiring_on[>]' => Registry::load('current_user')->time_stamp,
                            'site_users_membership.non_expiring[!]' => 1,
                            "site_users_membership.membership_expired" => 0,
                        ]
                    );

                    if (isset($user_membership[0])) {
                        $place_order = false;
                        $result = array();
                        $result['success'] = false;
                        $result['alert'] = Registry::load('strings')->membership_package_already_subscribed;
                    }
                }

                if ($place_order) {


                    DB::connect()->insert("membership_orders", [
                        "user_id" => $user_id,
                        "membership_package_id" => $data['membership_package_id'],
                        "order_status" => 1,
                        "created_on" => Registry::load('current_user')->time_stamp,
                        "updated_on" => Registry::load('current_user')->time_stamp,
                    ]);

                    if (!DB::connect()->error) {
                        $membership_order_id = DB::connect()->id();

                        if (!$free_package) {
                            include_once 'fns/wallet/load.php';

                            $transaction_info = ['order_type' => 'membership_package', 'order_id' => $membership_order_id];
                            $transaction_info = json_encode($transaction_info);

                            $wallet_data = [
                                'debit' => $package['pricing'],
                                'user_id' => $user_id,
                                'log_transaction' => $transaction_info
                            ];
                            UserWallet($wallet_data);
                        }


                        $non_expiring = 0;

                        if (!empty($package['is_recurring'])) {
                            $non_expiring = 1;
                            $expiring_on = Registry::load('current_user')->time_stamp;
                        } else {
                            $duration = 1;

                            $duration_in_minutes = $package['duration_in_minutes'];

                            if (!empty($package['duration'])) {
                                $duration = $package['duration'];
                            } else if (!empty($duration_in_minutes)) {
                                $duration = 0;
                            }

                            $expiring_on = Registry::load('current_user')->time_stamp;
                            $expiring_on = strtotime($expiring_on);
                            $expiring_on = strtotime('+'.$duration.' days +'.$duration_in_minutes.' minutes', $expiring_on);
                            $expiring_on = date('Y-m-d H:i:s', $expiring_on);
                        }

                        $membership_data = [
                            'user_id' => $user_id,
                            'membership_package_id' => $package['membership_package_id'],
                            'started_on' => Registry::load('current_user')->time_stamp,
                            'expiring_on' => $expiring_on,
                            'non_expiring' => $non_expiring,
                            'package_amount' => $package['pricing'],
                            'currency_code' => Registry::load('settings')->default_currency,
                            'membership_expired' => 0,
                            'notified_expiry' => 0,
                            'updated_on' => Registry::load('current_user')->time_stamp,
                        ];

                        $user_membership = DB::connect()->select('site_users_membership',
                            ['site_users_membership.membership_info_id'],
                            ['site_users_membership.user_id' => $user_id]
                        );

                        if (isset($user_membership[0])) {
                            DB::connect()->update('site_users_membership', $membership_data,
                                ['site_users_membership.user_id' => $user_id]);
                        } else {
                            DB::connect()->insert('site_users_membership', $membership_data);
                        }

                        $related_site_role_id = $package['related_site_role_id'];

                        DB::connect()->update('site_users', ['site_role_id' => $related_site_role_id],
                            ['site_users.user_id' => $user_id]);

                        remove_login_sesion_cache($user_id);


                        $result = array();
                        $result['success'] = true;
                        $result['redirect'] = Registry::load('config')->site_url.'membership_packages/';
                    }
                }
            }

        }
    }
}
?>