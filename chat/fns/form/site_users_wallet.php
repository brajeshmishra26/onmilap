<?php

if (role(['permissions' => ['wallet' => 'manage_user_wallet_funds']])) {

    $form = array();
    $form['loaded'] = new stdClass();
    $form['fields'] = new stdClass();

    if (isset($load["user_id"])) {

        $load["user_id"] = filter_var($load["user_id"], FILTER_SANITIZE_NUMBER_INT);

        if (!empty($load['user_id'])) {

            $columns = [
                'site_users.user_id', 'site_users.wallet_balance', 'site_users.display_name',
                'site_users.username'
            ];


            if (Registry::load('current_user')->site_role_attribute !== 'administrators') {
                $where['site_roles.role_hierarchy[<]'] = Registry::load('current_user')->role_hierarchy;
            }

            $join["[>]site_roles"] = ["site_users.site_role_id" => "site_role_id"];

            $where["site_users.user_id"] = $load["user_id"];
            $where["LIMIT"] = 1;

            $user_info = DB::connect()->select('site_users', $join, $columns, $where);

            if (isset($user_info[0])) {

                $user_info = $user_info[0];

                $form['loaded']->title = Registry::load('strings')->manage_wallet;
                $form['loaded']->button = Registry::load('strings')->update;

                $form['fields']->user_id = [
                    "tag" => 'input', "type" => 'hidden', "class" => 'd-none', "value" => $user_info["user_id"]
                ];

                $form['fields']->update = [
                    "tag" => 'input', "type" => 'hidden', "class" => 'd-none', "value" => "site_users_wallet"
                ];

                $form['fields']->full_name = [
                    "title" => Registry::load('strings')->full_name, "tag" => 'input', "type" => "text", "class" => 'field',
                    "value" => $user_info['display_name'], "attributes" => ['disabled' => 'disabled']
                ];

                $form['fields']->username = [
                    "title" => Registry::load('strings')->username, "tag" => 'input', "type" => "text", "class" => 'field',
                    "value" => $user_info['username'], "attributes" => ['disabled' => 'disabled']
                ];

                if (!empty($user_info['wallet_balance'])) {
                    $wallet_balance = $user_info['wallet_balance'];
                } else {
                    $wallet_balance = 0;
                }



                if (Registry::load('settings')->currency_symbol_placement === 'right') {
                    $wallet_balance = $wallet_balance.Registry::load('settings')->default_currency_symbol;
                } else {
                    $wallet_balance = Registry::load('settings')->default_currency_symbol . ' ' . $wallet_balance;
                }

                $form['fields']->wallet_balance = [
                    "title" => Registry::load('strings')->wallet_balance, "tag" => 'input', "type" => "text", "class" => 'field',
                    "value" => $wallet_balance, "attributes" => ['disabled' => 'disabled']
                ];

                $last_credit = DB::connect()->select(
                    'site_users_wallet',
                    ['wallet_amount', 'currency_code'],
                    ['user_id' => $user_info["user_id"], 'transaction_type' => 1, 'transaction_status' => 1, 'ORDER' => ['wallet_transaction_id' => "DESC"], 'LIMIT' => 1]
                );

                if (isset($last_credit[0])) {
                    $last_credit = $last_credit[0]['currency_code'] . ' ' . $last_credit[0]['wallet_amount'];
                } else {
                    $last_credit = '0.00';
                }

                $form['fields']->wallet_last_credit = [
                    "title" => Registry::load('strings')->wallet_last_credit, "tag" => 'input', "type" => "text", "class" => 'field',
                    "value" => $last_credit, "attributes" => ['disabled' => 'disabled']
                ];


                $form['fields']->take_action = [
                    "title" => Registry::load('strings')->take_action, "tag" => 'select', "class" => 'field',
                ];
                $form['fields']->take_action['options'] = [
                    "credit_funds" => Registry::load('strings')->credit_funds,
                    "deduct_funds" => Registry::load('strings')->deduct_funds,
                ];

                $form['fields']->amount = [
                    "title" => Registry::load('strings')->enter_amount, "tag" => 'input',
                    "type" => "number", "class" => 'field',
                ];



            }
        }
    }
}
?>