<?php

if (role(['permissions' => ['wallet' => ['withdraw_money', 'manage_withdrawals']], 'condition' => 'OR'])) {

    $columns = [
        'withdrawal_requests.withdrawal_request_id', 'withdrawal_requests.user_id',
        'withdrawal_requests.withdrawal_amount',
        'withdrawal_requests.currency_code',
        'withdrawal_requests.withdrawal_status',
        'withdrawal_requests.created_on',
        'site_users.username', 'site_users.email_address'
    ];

    $join["[>]site_users"] = ["withdrawal_requests.user_id" => "user_id"];

    if (!empty($data["offset"])) {
        $data["offset"] = array_map('intval', explode(',', $data["offset"]));
        $where["withdrawal_requests.withdrawal_request_id[!]"] = $data["offset"];
    }

    if (!empty($data["search"])) {
        if (isset($private_data["manage_withdrawals"])) {
            if (filter_var($data["search"], FILTER_VALIDATE_EMAIL)) {
                $where["site_users.email_address[~]"] = $data["search"];
            } else if (is_numeric($data["search"])) {
                $where["withdrawal_requests.withdrawal_request_id"] = $data["search"];
            } else {
                $where["site_users.username[~]"] = $data["search"];
            }

        } else {
            $where["withdrawal_requests.withdrawal_request_id[~]"] = $data["search"];
        }
    }

    if (!isset($private_data["manage_withdrawals"])) {
        $load_data = 'withdrawals';
        $where["withdrawal_requests.user_id"] = Registry::load('current_user')->id;
    } else {
        $load_data = 'withdrawal_requests';
    }

    $where["LIMIT"] = Registry::load('settings')->records_per_call;
    $where["ORDER"] = ["withdrawal_requests.withdrawal_request_id" => "DESC"];

    $withdrawal_requests = DB::connect()->select('withdrawal_requests', $join, $columns, $where);

    $i = 1;
    $output = array();
    $output['loaded'] = new stdClass();
    $output['loaded']->title = Registry::load('strings')->withdrawals;
    $output['loaded']->loaded = $load_data;
    $output['loaded']->offset = array();

    if (!empty($data["offset"])) {
        $output['loaded']->offset = $data["offset"];
    }


    foreach ($withdrawal_requests as $withdrawal_request) {
        $output['loaded']->offset[] = $withdrawal_request['withdrawal_request_id'];


        $status_symbol = Registry::load('config')->site_url . 'assets/files/defaults/pending.png';
        $withdrawal_status = Registry::load('strings')->pending;

        if ((int) $withdrawal_request['withdrawal_status'] === 1) {
            $status_symbol = Registry::load('config')->site_url . 'assets/files/defaults/successful.png';
            $withdrawal_status = Registry::load('strings')->successful;
        } else if ((int) $withdrawal_request['withdrawal_status'] === 2) {
            $status_symbol = Registry::load('config')->site_url . 'assets/files/defaults/failed.png';
            $withdrawal_status = Registry::load('strings')->rejected;
        }

        $output['content'][$i] = new stdClass();
        $output['content'][$i]->title = $withdrawal_request['currency_code'].' '.$withdrawal_request['withdrawal_amount'];
        $output['content'][$i]->title .= ' ['.Registry::load('strings')->id.': '.$withdrawal_request['withdrawal_request_id'].']';
        $output['content'][$i]->identifier = $withdrawal_request['withdrawal_request_id'];
        $output['content'][$i]->class = "withdrawal_request";

        $output['content'][$i]->image = $status_symbol;
        $output['content'][$i]->subtitle = $withdrawal_status;


        if (isset($private_data["manage_withdrawals"])) {
            $output['content'][$i]->subtitle .= ' [@'.$withdrawal_request['username'].']';
        }

        $output['content'][$i]->icon = 0;
        $output['content'][$i]->unread = 0;

        $index = 1;

        $output['options'][$i][$index] = new stdClass();

        if (isset($private_data["manage_withdrawals"])) {
            $output['options'][$i][$index]->option = Registry::load('strings')->manage;
        } else {
            $output['options'][$i][$index]->option = Registry::load('strings')->view;
        }

        $output['options'][$i][$index]->class = 'load_form';
        $output['options'][$i][$index]->attributes['form'] = 'withdrawal_requests';
        $output['options'][$i][$index]->attributes['data-withdrawal_request_id'] = $withdrawal_request['withdrawal_request_id'];
        $index++;


        $i++;
    }
}
?>