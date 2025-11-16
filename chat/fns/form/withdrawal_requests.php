<?php

if (role(['permissions' => ['wallet' => ['withdraw_money', 'manage_withdrawals']], 'condition' => 'OR'])) {

    $form = array();
    $form['loaded'] = new stdClass();
    $form['fields'] = new stdClass();

    if (isset($load["withdrawal_request_id"])) {

        $load["withdrawal_request_id"] = filter_var($load["withdrawal_request_id"], FILTER_SANITIZE_NUMBER_INT);

        if (!empty($load['withdrawal_request_id'])) {

            $columns = [
                'withdrawal_requests.withdrawal_request_id', 'withdrawal_requests.user_id',
                'withdrawal_requests.withdrawal_amount',
                'withdrawal_requests.currency_code',
                'withdrawal_requests.withdrawal_status',
                'withdrawal_requests.reference_note',
                'withdrawal_requests.transfer_details',
                'withdrawal_requests.created_on',
                'withdrawal_requests.updated_on',
                'site_users.display_name', 'site_users.username', 'site_users.email_address'
            ];
            $join["[>]site_users"] = ["withdrawal_requests.user_id" => "user_id"];

            $where["withdrawal_requests.withdrawal_request_id"] = $load["withdrawal_request_id"];

            if (!role(['permissions' => ['wallet' => 'manage_withdrawals']])) {
                $where["withdrawal_requests.user_id"] = Registry::load('current_user')->id;
            }

            $where["LIMIT"] = 1;

            $withdrawal_request = DB::connect()->select('withdrawal_requests', $join, $columns, $where);

            if (isset($withdrawal_request[0])) {

                $withdrawal_request = $withdrawal_request[0];

                $form['loaded']->title = Registry::load('strings')->withdrawal_request;

                if (role(['permissions' => ['wallet' => 'manage_withdrawals']])) {
                    $form['loaded']->button = Registry::load('strings')->update;
                }


                $form['fields']->withdrawal_request_id = [
                    "tag" => 'input', "type" => 'hidden', "class" => 'd-none', "value" => $load["withdrawal_request_id"]
                ];

                $form['fields']->update = [
                    "tag" => 'input', "type" => 'hidden', "class" => 'd-none', "value" => "withdrawal_requests"
                ];

                $form['fields']->withdrawal_request_identifier = [
                    "title" => Registry::load('strings')->id, "tag" => 'input', "type" => "text", "class" => 'field',
                    "value" => $withdrawal_request['withdrawal_request_id'], "attributes" => ['disabled' => 'disabled']
                ];


                $form['fields']->full_name = [
                    "title" => Registry::load('strings')->full_name, "tag" => 'input', "type" => "text", "class" => 'field',
                    "value" => $withdrawal_request['display_name'], "attributes" => ['disabled' => 'disabled']
                ];

                $form['fields']->username = [
                    "title" => Registry::load('strings')->username, "tag" => 'input', "type" => "text", "class" => 'field',
                    "value" => $withdrawal_request['username'], "attributes" => ['disabled' => 'disabled']
                ];

                $form['fields']->withdrawal_amount = [
                    "title" => Registry::load('strings')->withdrawal_amount, "tag" => 'input', "type" => "text", "class" => 'field',
                    "value" => $withdrawal_request['currency_code'].' '.$withdrawal_request['withdrawal_amount'],
                    "attributes" => ['disabled' => 'disabled']
                ];


                $form['fields']->transfer_details = [
                    "title" => Registry::load('strings')->transfer_details, "tag" => 'textarea', "class" => 'field',
                    "value" => $withdrawal_request['transfer_details'],
                    "attributes" => ['disabled' => 'disabled']
                ];


                if (role(['permissions' => ['wallet' => 'manage_withdrawals']])) {

                    $form['fields']->take_action = [
                        "title" => Registry::load('strings')->take_action,
                        "tag" => 'select',
                        "class" => 'field',
                    ];

                    $form['fields']->take_action['options'] = [
                        "approve" => Registry::load('strings')->approve,
                        "disapprove" => Registry::load('strings')->disapprove,
                    ];

                    $form['fields']->reference_note = [
                        "title" => Registry::load('strings')->reference_note, "tag" => 'textarea', "class" => 'field',
                        "value" => $withdrawal_request['reference_note']
                    ];
                } else {
                    $form['fields']->reference_note = [
                        "title" => Registry::load('strings')->reference_note, "tag" => 'textarea', "class" => 'field',
                        "value" => $withdrawal_request['reference_note'], "attributes" => ['disabled' => 'disabled']
                    ];
                }


                if ((int)$withdrawal_request['withdrawal_status'] === 1) {
                    $withdrawal_request_status = Registry::load('strings')->successful;
                } else if ((int)$withdrawal_request['withdrawal_status'] === 0) {
                    $withdrawal_request_status = Registry::load('strings')->pending;
                } else {
                    $withdrawal_request_status = Registry::load('strings')->failed;
                }

                $form['fields']->withdrawal_status = [
                    "title" => Registry::load('strings')->status, "tag" => 'input', "type" => "text", "class" => 'field',
                    "value" => $withdrawal_request_status, "attributes" => ['disabled' => 'disabled']
                ];

                $created_on = array();
                $created_on['date'] = $withdrawal_request['created_on'];
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