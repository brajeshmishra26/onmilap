<?php
if (Registry::load('settings')->tips_system === 'enable') {
    if (role(['permissions' => ['tips' => ['send_tips', 'recieve_tips']], 'condition' => 'OR'])) {

        $current_user_id = Registry::load('current_user')->id;

        $columns = [
            'site_user_tips.received_user_id', 'site_user_tips.sent_user_id',
            'site_user_tips.tip_amount', 'site_user_tips.user_tip_id',
            'site_user_tips.currency_code',
            'site_user_tips.created_on',
            'recieved_user.username(recieved_user)', 'sent_user.username(sent_user)',
        ];

        $join["[>]site_users(recieved_user)"] = ["site_user_tips.received_user_id" => "user_id"];
        $join["[>]site_users(sent_user)"] = ["site_user_tips.sent_user_id" => "user_id"];

        if (!empty($data["offset"])) {
            $data["offset"] = array_map('intval', explode(',', $data["offset"]));
            $where["site_user_tips.user_tip_id[!]"] = $data["offset"];
        }

        if (!empty($data["search"])) {

            if (is_numeric($data["search"])) {
                $where["site_user_tips.user_tip_id"] = $data["search"];
            } else {
                $where["AND #search_query"]["OR"]["AND #first_query"] = [
                    "recieved_user.display_name[~]" => $data["search"],
                    "recieved_user.username[~]" => $data["search"],
                ];

                $where["AND #search_query"]["OR"]["AND #second_query"] = [
                    "sent_user.display_name[~]" => $data["search"],
                    "sent_user.username[~]" => $data["search"],
                ];
            }
        }

        if (isset($data["user_id"]) && !empty($data["user_id"])) {
            $where["AND"]["OR #first_query"] = [
                "AND #first_query" => ["site_user_tips.received_user_id" => $current_user_id, "site_user_tips.sent_user_id" => $data["user_id"]],
                "AND #second_query" => ["site_user_tips.received_user_id" => $data["user_id"], "site_user_tips.sent_user_id" => $current_user_id],
            ];
        } else {
            $where["AND"]["OR #first_query"] = [
                "site_user_tips.received_user_id" => $current_user_id,
                "site_user_tips.sent_user_id" => $current_user_id,
            ];
        }

        $where["LIMIT"] = Registry::load('settings')->records_per_call;
        $where["ORDER"] = ["site_user_tips.user_tip_id" => "DESC"];
        $site_user_tips = DB::connect()->select('site_user_tips', $join, $columns, $where);

        $i = 1;
        $output = array();
        $output['loaded'] = new stdClass();
        $output['loaded']->title = Registry::load('strings')->tips;
        $output['loaded']->loaded = 'tips_history';
        $output['loaded']->offset = array();

        if (!empty($data["offset"])) {
            $output['loaded']->offset = $data["offset"];
        }

        foreach ($site_user_tips as $site_user_tip) {
            $output['loaded']->offset[] = $site_user_tip['user_tip_id'];

            if ((int)$site_user_tip['received_user_id'] === (int)$current_user_id) {
                $tip_info = Registry::load('strings')->received_from;
                $tip_info .= ' @'.$site_user_tip['sent_user'];
            } else if ((int)$site_user_tip['sent_user_id'] === (int)$current_user_id) {
                $tip_info = Registry::load('strings')->sent_to;
                $tip_info .= ' @'.$site_user_tip['recieved_user'];
            }

            $output['content'][$i] = new stdClass();
            $output['content'][$i]->title = $site_user_tip['currency_code'].' '.$site_user_tip['tip_amount'];
            $output['content'][$i]->title .= ' ['.Registry::load('strings')->id.': '.$site_user_tip['user_tip_id'].']';
            $output['content'][$i]->identifier = $site_user_tip['user_tip_id'];
            $output['content'][$i]->class = "user_tip load_form";
            $output['content'][$i]->attributes['form'] = 'user_tips';
            $output['content'][$i]->attributes['data-user_tip_id'] = $site_user_tip['user_tip_id'];

            if ((int)$site_user_tip['received_user_id'] === (int)$current_user_id) {
                $output['content'][$i]->image = Registry::load('config')->site_url."assets/files/defaults/received_icon.png";
            } else {
                $output['content'][$i]->image = Registry::load('config')->site_url."assets/files/defaults/sent_icon.png";
            }

            $output['content'][$i]->subtitle = $tip_info;

            $output['content'][$i]->icon = 0;
            $output['content'][$i]->unread = 0;

            $index = 1;



            $i++;
        }
    }
}
?>