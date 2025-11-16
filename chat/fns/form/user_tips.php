<?php
if (Registry::load('settings')->tips_system === 'enable') {
    if (role(['permissions' => ['tips' => ['send_tips', 'recieve_tips']], 'condition' => 'OR'])) {

        $form = array();
        $form['loaded'] = new stdClass();
        $form['fields'] = new stdClass();

        if (isset($load["user_tip_id"])) {

            $load["user_tip_id"] = filter_var($load["user_tip_id"], FILTER_SANITIZE_NUMBER_INT);

            if (!empty($load['user_tip_id'])) {

                $current_user_id = Registry::load('current_user')->id;

                $columns = [
                    'site_user_tips.received_user_id', 'site_user_tips.sent_user_id',
                    'site_user_tips.tip_amount', 'site_user_tips.user_tip_id',
                    'site_user_tips.currency_code', 'site_user_tips.message', 'site_user_tips.created_on',
                    'recieved_user.username(recieved_user)', 'sent_user.username(sent_user)',
                ];

                $join["[>]site_users(recieved_user)"] = ["site_user_tips.received_user_id" => "user_id"];
                $join["[>]site_users(sent_user)"] = ["site_user_tips.sent_user_id" => "user_id"];

                $where["AND"]["OR #first_query"] = [
                    "site_user_tips.received_user_id" => $current_user_id,
                    "site_user_tips.sent_user_id" => $current_user_id,
                ];

                $where["site_user_tips.user_tip_id"] = $load["user_tip_id"];
                $where["LIMIT"] = 1;

                $site_user_tip = DB::connect()->select('site_user_tips', $join, $columns, $where);

                if (isset($site_user_tip[0])) {

                    $site_user_tip = $site_user_tip[0];

                    $form['loaded']->title = Registry::load('strings')->tips;

                    $form['fields']->user_tip_id = [
                        "tag" => 'input', "type" => 'hidden', "class" => 'd-none', "value" => $load["user_tip_id"]
                    ];

                    if ((int)$site_user_tip['received_user_id'] === (int)$current_user_id) {
                        $form['fields']->received_from = [
                            "title" => Registry::load('strings')->received_from, "tag" => 'input', "type" => "text", "class" => 'field',
                            "value" => ' @'.$site_user_tip['sent_user'], "attributes" => ['disabled' => 'disabled']
                        ];

                    } else if ((int)$site_user_tip['sent_user_id'] === (int)$current_user_id) {
                        $form['fields']->sent_to = [
                            "title" => Registry::load('strings')->sent_to, "tag" => 'input', "type" => "text", "class" => 'field',
                            "value" => ' @'.$site_user_tip['recieved_user'], "attributes" => ['disabled' => 'disabled']
                        ];
                    }


                    $form['fields']->amount = [
                        "title" => Registry::load('strings')->amount, "tag" => 'input', "type" => "text", "class" => 'field',
                        "value" => $site_user_tip['currency_code'].' '.$site_user_tip['tip_amount'], "attributes" => ['disabled' => 'disabled']
                    ];

                    $trans_date = array();
                    $trans_date['date'] = $site_user_tip['created_on'];
                    $trans_date['auto_format'] = true;
                    $trans_date['include_time'] = true;
                    $trans_date['timezone'] = Registry::load('current_user')->time_zone;
                    $trans_date = get_date($trans_date);

                    $form['fields']->trans_date = [
                        "title" => Registry::load('strings')->trans_date, "tag" => 'input', "type" => "text", "class" => 'field',
                        "value" => $trans_date['date'].' '.$trans_date['time'], "attributes" => ['disabled' => 'disabled']
                    ];

                    if (!empty($site_user_tip['message'])) {
                        $form['fields']->tip_message = [
                            "title" => Registry::load('strings')->tip_message, "tag" => 'textarea', "type" => "text", "class" => 'field',
                            "value" => $site_user_tip['message'], "attributes" => ['disabled' => 'disabled']
                        ];
                    }


                }
            }
        }
    }
}
?>