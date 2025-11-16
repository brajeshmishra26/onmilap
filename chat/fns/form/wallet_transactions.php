<?php

if (role(['permissions' => ['wallet' => ['view_personal_transactions', 'view_site_transactions']], 'condition' => 'OR'])) {

    $form = array();
    $form['loaded'] = new stdClass();
    $form['fields'] = new stdClass();

    if (isset($load["wallet_transaction_id"])) {

        $load["wallet_transaction_id"] = filter_var($load["wallet_transaction_id"], FILTER_SANITIZE_NUMBER_INT);

        if (!empty($load['wallet_transaction_id'])) {

            $columns = [
                'site_users_wallet.wallet_transaction_id', 'site_users_wallet.user_id',
                'site_users_wallet.wallet_amount',
                'site_users_wallet.currency_code',
                'site_users_wallet.transaction_type',
                'site_users_wallet.transaction_info',
                'site_users_wallet.transaction_status',
                'site_users_wallet.created_on',
                'site_users.display_name', 'site_users.username',
                'payment_gateways.identifier',
                'site_users_wallet.wallet_fund_status',
            ];
            $join["[>]payment_gateways"] = ['site_users_wallet.payment_gateway_id' => 'payment_gateway_id'];
            $join["[>]site_users"] = ["site_users_wallet.user_id" => "user_id"];

            $where["site_users_wallet.wallet_transaction_id"] = $load["wallet_transaction_id"];

            if (!role(['permissions' => ['wallet' => 'view_site_transactions']])) {
                $where["site_users_wallet.user_id"] = Registry::load('current_user')->id;
            }

            $where["LIMIT"] = 1;

            $wallet_transaction = DB::connect()->select('site_users_wallet', $join, $columns, $where);

            if (isset($wallet_transaction[0])) {

                $wallet_transaction = $wallet_transaction[0];

                $form['loaded']->title = Registry::load('strings')->view_order;

                if (role(['permissions' => ['memberships' => 'edit_site_transactions']])) {
                    $form['loaded']->title = Registry::load('strings')->edit_order;
                    $form['loaded']->button = Registry::load('strings')->update;
                }


                $form['fields']->wallet_transaction_id = [
                    "tag" => 'input', "type" => 'hidden', "class" => 'd-none', "value" => $load["wallet_transaction_id"]
                ];

                $form['fields']->update = [
                    "tag" => 'input', "type" => 'hidden', "class" => 'd-none', "value" => "wallet_transactions"
                ];

                $form['fields']->wallet_transaction_identifier = [
                    "title" => Registry::load('strings')->transaction_id, "tag" => 'input', "type" => "text", "class" => 'field',
                    "value" => $wallet_transaction['wallet_transaction_id'], "attributes" => ['disabled' => 'disabled']
                ];


                $form['fields']->full_name = [
                    "title" => Registry::load('strings')->full_name, "tag" => 'input', "type" => "text", "class" => 'field',
                    "value" => $wallet_transaction['display_name'], "attributes" => ['disabled' => 'disabled']
                ];

                $form['fields']->username = [
                    "title" => Registry::load('strings')->username, "tag" => 'input', "type" => "text", "class" => 'field',
                    "value" => $wallet_transaction['username'], "attributes" => ['disabled' => 'disabled']
                ];


                if (role(['permissions' => ['wallet' => 'edit_site_transactions']])) {

                    $form['fields']->take_action = [
                        "title" => Registry::load('strings')->take_action,
                        "tag" => 'select',
                        "class" => 'field',
                    ];

                    $form['fields']->take_action['options'] = [
                        "approve" => Registry::load('strings')->approve,
                        "disapprove" => Registry::load('strings')->disapprove,
                    ];


                    if ((int)$wallet_transaction['transaction_type'] === 1) {
                        if ((int)$wallet_transaction['wallet_fund_status'] !== 1) {
                            $form['fields']->take_action['options']['approve_topup'] = Registry::load('strings')->approve_topup;
                        } else {
                            $form['fields']->take_action['options']['disapprove_debit'] = Registry::load('strings')->disapprove_debit;

                        }
                    } else {
                        if ((int)$wallet_transaction['wallet_fund_status'] !== 1) {
                            $form['fields']->take_action['options']['approve_debit'] = Registry::load('strings')->approve_debit;
                        } else {
                            $form['fields']->take_action['options']['disapprove_credit'] = Registry::load('strings')->disapprove_credit;
                        }
                    }
                }

                if (!empty($wallet_transaction['transaction_info'])) {
                    $transaction_info = json_decode($wallet_transaction['transaction_info'], true);

                    if (!empty($transaction_info) && isset($transaction_info['order_type'])) {
                        $order_type = $transaction_info['order_type'];
                        $form['fields']->order_type = [
                            "title" => Registry::load('strings')->order_type, "tag" => 'input', "type" => "text", "class" => 'field',
                            "value" => Registry::load('strings')->$order_type,
                            "attributes" => ['disabled' => 'disabled']
                        ];

                        if ($order_type === 'received_tip' || $order_type === 'sent_tip') {
                            if (isset($transaction_info['received_from'])) {
                                $received_from = DB::connect()->select('site_users', ['display_name', 'username'], ['user_id' => $transaction_info['received_from'], 'LIMIT' => 1]);

                                if (isset($received_from[0])) {
                                    $received_from = $received_from[0]['display_name'].' [@'.$received_from[0]['username'].']';
                                    $form['fields']->received_from = [
                                        "title" => Registry::load('strings')->received_from, "tag" => 'input', "type" => "text", "class" => 'field',
                                        "value" => $received_from,
                                        "attributes" => ['disabled' => 'disabled']
                                    ];
                                }
                            } else if (isset($transaction_info['sent_to'])) {
                                $sent_to = DB::connect()->select('site_users', ['display_name', 'username'], ['user_id' => $transaction_info['sent_to'], 'LIMIT' => 1]);

                                if (isset($sent_to[0])) {
                                    $sent_to = $sent_to[0]['display_name'].' [@'.$sent_to[0]['username'].']';
                                    $form['fields']->sent_to = [
                                        "title" => Registry::load('strings')->sent_to, "tag" => 'input', "type" => "text", "class" => 'field',
                                        "value" => $sent_to,
                                        "attributes" => ['disabled' => 'disabled']
                                    ];
                                }
                            }
                        }

                        if (isset($transaction_info['action_by'])) {
                            if (role(['permissions' => ['wallet' => 'manage_user_wallet_funds']])) {
                                $action_by = (int)$transaction_info['action_by'];

                                if (!empty($action_by)) {
                                    $action_taken_by = DB::connect()->select('site_users', ['display_name'], ['user_id' => $action_by, 'LIMIT' => 1]);

                                    if (isset($action_taken_by[0])) {
                                        $action_taken_by = $action_taken_by[0]['display_name'].' [ID - '.$action_by.']';
                                    } else {
                                        $action_taken_by = '[ID - '.$action_by.']';
                                    }


                                    $form['fields']->action_taken_by = [
                                        "title" => Registry::load('strings')->action_taken_by, "tag" => 'input', "type" => "text", "class" => 'field',
                                        "value" => $action_taken_by,
                                        "attributes" => ['disabled' => 'disabled']
                                    ];
                                }
                            }
                        }

                        if (isset($transaction_info['order_id'])) {
                            $form['fields']->order_id = [
                                "title" => Registry::load('strings')->order_id, "tag" => 'input', "type" => "text", "class" => 'field',
                                "value" => $transaction_info['order_id'],
                                "attributes" => ['disabled' => 'disabled']
                            ];

                        }
                    }
                }



                $form['fields']->pricing = [
                    "title" => Registry::load('strings')->pricing, "tag" => 'input', "type" => "text", "class" => 'field',
                    "value" => $wallet_transaction['currency_code'].' '.$wallet_transaction['wallet_amount'],
                    "attributes" => ['disabled' => 'disabled']
                ];



                if (!empty($wallet_transaction['identifier'])) {

                    $payment_method = str_replace('_', ' ', $wallet_transaction['identifier']);

                    $form['fields']->payment_method = [
                        "title" => Registry::load('strings')->payment_method, "tag" => 'input', "type" => "text", "class" => 'field',
                        "value" => ucwords($payment_method),
                        "attributes" => ['disabled' => 'disabled']
                    ];
                }

                $transaction_type = Registry::load('strings')->debit;

                if ((int)$wallet_transaction['transaction_type'] === 1) {
                    $transaction_type = Registry::load('strings')->credit;
                }

                $form['fields']->transaction_type = [
                    "title" => Registry::load('strings')->debit_credit, "tag" => 'input', "type" => "text", "class" => 'field',
                    "value" => $transaction_type,
                    "attributes" => ['disabled' => 'disabled']
                ];


                if ((int)$wallet_transaction['transaction_status'] === 1) {
                    $wallet_transaction_status = Registry::load('strings')->successful;
                } else if ((int)$wallet_transaction['transaction_status'] === 0) {
                    $wallet_transaction_status = Registry::load('strings')->pending;
                } else {
                    $wallet_transaction_status = Registry::load('strings')->failed;
                }

                $form['fields']->transaction_status = [
                    "title" => Registry::load('strings')->status, "tag" => 'input', "type" => "text", "class" => 'field',
                    "value" => $wallet_transaction_status, "attributes" => ['disabled' => 'disabled']
                ];

                $created_on = array();
                $created_on['date'] = $wallet_transaction['created_on'];
                $created_on['auto_format'] = true;
                $created_on['include_time'] = true;
                $created_on['timezone'] = Registry::load('current_user')->time_zone;
                $created_on = get_date($created_on);

                $form['fields']->date_text = [
                    "title" => Registry::load('strings')->date_text, "tag" => 'input', "type" => "text", "class" => 'field',
                    "value" => $created_on['date'].' '.$created_on['time'], "attributes" => ['disabled' => 'disabled']
                ];

            }
        }
    }
}
?>