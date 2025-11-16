<?php

if (role(['permissions' => ['wallet' => ['view_personal_transactions', 'view_site_transactions']], 'condition' => 'OR'])) {

    $columns = [
        'site_users_wallet.wallet_transaction_id', 'site_users_wallet.user_id',
        'site_users_wallet.wallet_amount',
        'site_users_wallet.currency_code',
        'site_users_wallet.transaction_type',
        'site_users_wallet.transaction_status',
        'site_users_wallet.created_on',
        'site_users.username', 'site_users.email_address'
    ];

    $join["[>]site_users"] = ["site_users_wallet.user_id" => "user_id"];

    if (!empty($data["offset"])) {
        $data["offset"] = array_map('intval', explode(',', $data["offset"]));
        $where["site_users_wallet.wallet_transaction_id[!]"] = $data["offset"];
    }

    if (!empty($data["search"])) {
        if (isset($private_data["site_transactions"])) {
            if (filter_var($data["search"], FILTER_VALIDATE_EMAIL)) {
                $where["site_users.email_address[~]"] = $data["search"];
            } else if (is_numeric($data["search"])) {
                $where["site_users_wallet.wallet_transaction_id"] = $data["search"];
            } else {
                $where["site_users.username[~]"] = $data["search"];
            }

        } else {
            $where["site_users_wallet.wallet_transaction_id[~]"] = $data["search"];
        }
    }

    if (!isset($private_data["site_transactions"])) {
        $load_data = 'wallet_transactions';
        $where["site_users_wallet.user_id"] = Registry::load('current_user')->id;
    } else {
        $load_data = 'site_wallet_transactions';
    }

    $where["LIMIT"] = Registry::load('settings')->records_per_call;

    if ($data["sortby"] === 'wallet_transaction_id_asc') {
        $where["ORDER"] = ["site_users_wallet.wallet_transaction_id" => "ASC"];
    } else if ($data["sortby"] === 'wallet_transaction_id_desc') {
        $where["ORDER"] = ["site_users_wallet.wallet_transaction_id" => "DESC"];
    } else if ($data["sortby"] === 'status_asc') {
        $where["ORDER"] = ["site_users_wallet.transaction_status" => "ASC"];
    } else if ($data["sortby"] === 'status_desc') {
        $where["ORDER"] = ["site_users_wallet.transaction_status" => "DESC"];
    } else if ($data["sortby"] === 'type_asc') {
        $where["ORDER"] = ["site_users_wallet.transaction_type" => "ASC"];
    } else if ($data["sortby"] === 'type_desc') {
        $where["ORDER"] = ["site_users_wallet.transaction_type" => "DESC"];
    } else {
        $where["ORDER"] = ["site_users_wallet.wallet_transaction_id" => "DESC"];
    }

    $wallet_transactions = DB::connect()->select('site_users_wallet', $join, $columns, $where);

    $i = 1;
    $output = array();
    $output['loaded'] = new stdClass();
    $output['loaded']->title = Registry::load('strings')->transactions;
    $output['loaded']->loaded = $load_data;
    $output['loaded']->offset = array();

    if (!empty($data["offset"])) {
        $output['loaded']->offset = $data["offset"];
    }

    if (role(['permissions' => ['wallet' => 'delete_site_transactions']])) {
        $output['multiple_select'] = new stdClass();
        $output['multiple_select']->title = Registry::load('strings')->delete;
        $output['multiple_select']->attributes['class'] = 'ask_confirmation';
        $output['multiple_select']->attributes['data-remove'] = 'wallet_transactions';
        $output['multiple_select']->attributes['multi_select'] = 'wallet_transaction_id';
        $output['multiple_select']->attributes['submit_button'] = Registry::load('strings')->yes;
        $output['multiple_select']->attributes['cancel_button'] = Registry::load('strings')->no;
        $output['multiple_select']->attributes['confirmation'] = Registry::load('strings')->confirm_action;
    }

    $output['sortby'][1] = new stdClass();
    $output['sortby'][1]->sortby = Registry::load('strings')->sort_by_default;
    $output['sortby'][1]->class = 'load_aside';
    $output['sortby'][1]->attributes['load'] = $load_data;

    $output['sortby'][2] = new stdClass();
    $output['sortby'][2]->sortby = Registry::load('strings')->transaction_id;
    $output['sortby'][2]->class = 'load_aside sort_asc';
    $output['sortby'][2]->attributes['load'] = $load_data;
    $output['sortby'][2]->attributes['sort'] = 'wallet_transaction_id_asc';

    $output['sortby'][3] = new stdClass();
    $output['sortby'][3]->sortby = Registry::load('strings')->transaction_id;
    $output['sortby'][3]->class = 'load_aside sort_desc';
    $output['sortby'][3]->attributes['load'] = $load_data;
    $output['sortby'][3]->attributes['sort'] = 'wallet_transaction_id_desc';

    $output['sortby'][4] = new stdClass();
    $output['sortby'][4]->sortby = Registry::load('strings')->status;
    $output['sortby'][4]->class = 'load_aside sort_asc';
    $output['sortby'][4]->attributes['load'] = $load_data;
    $output['sortby'][4]->attributes['sort'] = 'status_asc';

    $output['sortby'][5] = new stdClass();
    $output['sortby'][5]->sortby = Registry::load('strings')->status;
    $output['sortby'][5]->class = 'load_aside sort_desc';
    $output['sortby'][5]->attributes['load'] = $load_data;
    $output['sortby'][5]->attributes['sort'] = 'status_desc';

    $output['sortby'][6] = new stdClass();
    $output['sortby'][6]->sortby = Registry::load('strings')->type;
    $output['sortby'][6]->class = 'load_aside sort_asc';
    $output['sortby'][6]->attributes['load'] = $load_data;
    $output['sortby'][6]->attributes['sort'] = 'type_asc';

    $output['sortby'][7] = new stdClass();
    $output['sortby'][7]->sortby = Registry::load('strings')->type;
    $output['sortby'][7]->class = 'load_aside sort_desc';
    $output['sortby'][7]->attributes['load'] = $load_data;
    $output['sortby'][7]->attributes['sort'] = 'type_desc';


    foreach ($wallet_transactions as $wallet_transaction) {
        $output['loaded']->offset[] = $wallet_transaction['wallet_transaction_id'];


        $transaction_symbol = Registry::load('config')->site_url . 'assets/files/defaults/debit_symbol.png';
        $transaction_status = Registry::load('strings')->debit;

        if ((int) $wallet_transaction['transaction_type'] === 1) {
            $transaction_symbol = Registry::load('config')->site_url . 'assets/files/defaults/credit_symbol.png';
            $transaction_status = Registry::load('strings')->credit;
        }

        $output['content'][$i] = new stdClass();
        $output['content'][$i]->title = Registry::load('strings')->id.': '.$wallet_transaction['wallet_transaction_id'];
        $output['content'][$i]->title .= ' ['.$transaction_status.' '.$wallet_transaction['currency_code'].' '.$wallet_transaction['wallet_amount'].']';
        $output['content'][$i]->identifier = $wallet_transaction['wallet_transaction_id'];
        $output['content'][$i]->class = "wallet_transaction";

        $output['content'][$i]->image = $transaction_symbol;

        if ((int)$wallet_transaction['transaction_status'] === 1) {
            $output['content'][$i]->subtitle = Registry::load('strings')->successful;
        } else if ((int)$wallet_transaction['transaction_status'] === 0) {
            $output['content'][$i]->subtitle = Registry::load('strings')->pending;
            $output['content'][$i]->image = Registry::load('config')->site_url."assets/files/defaults/pending.png";
        } else {
            $output['content'][$i]->subtitle = Registry::load('strings')->failed;
            $output['content'][$i]->image = Registry::load('config')->site_url."assets/files/defaults/failed.png";
        }

        if (isset($private_data["site_transactions"])) {
            $output['content'][$i]->subtitle .= ' [@'.$wallet_transaction['username'].']';
        }

        $output['content'][$i]->icon = 0;
        $output['content'][$i]->unread = 0;

        $index = 1;

        $output['options'][$i][$index] = new stdClass();

        if (role(['permissions' => ['wallet' => 'edit_site_transactions']])) {
            $output['options'][$i][$index]->option = Registry::load('strings')->edit_order;
        } else {
            $output['options'][$i][$index]->option = Registry::load('strings')->view_order;
        }

        $output['options'][$i][$index]->class = 'load_form';
        $output['options'][$i][$index]->attributes['form'] = 'wallet_transactions';
        $output['options'][$i][$index]->attributes['data-wallet_transaction_id'] = $wallet_transaction['wallet_transaction_id'];
        $index++;

        if ((int)$wallet_transaction['transaction_status'] === 1 && (int)$wallet_transaction['transaction_type'] === 1) {
            if (role(['permissions' => ['wallet' => 'download_invoice']])) {
                $output['options'][$i][$index] = new stdClass();
                $output['options'][$i][$index]->option = Registry::load('strings')->invoice;
                $output['options'][$i][$index]->class = 'download_file';
                $output['options'][$i][$index]->attributes['download'] = 'invoice';
                $output['options'][$i][$index]->attributes['data-wallet_transaction_id'] = $wallet_transaction['wallet_transaction_id'];
                $index++;
            }
        } else if (!isset($private_data["site_transactions"]) && (int)$wallet_transaction['transaction_status'] === 0 && (int)$wallet_transaction['transaction_type'] === 1) {
            $validation_url = Registry::load('config')->site_url.'topup_wallet/'.$wallet_transaction['wallet_transaction_id'].'/';
            $output['options'][$i][$index] = new stdClass();
            $output['options'][$i][$index]->option = Registry::load('strings')->validate;
            $output['options'][$i][$index]->class = 'openlink';
            $output['options'][$i][$index]->attributes['url'] = $validation_url;
            $index++;
        }

        if (role(['permissions' => ['wallet' => 'delete_site_transactions']])) {
            $output['options'][$i][$index] = new stdClass();
            $output['options'][$i][$index]->option = Registry::load('strings')->delete;
            $output['options'][$i][$index]->class = 'ask_confirmation';
            $output['options'][$i][$index]->attributes['data-remove'] = 'wallet_transactions';
            $output['options'][$i][$index]->attributes['data-wallet_transaction_id'] = $wallet_transaction['wallet_transaction_id'];
            $output['options'][$i][$index]->attributes['submit_button'] = Registry::load('strings')->yes;
            $output['options'][$i][$index]->attributes['cancel_button'] = Registry::load('strings')->no;
            $output['options'][$i][$index]->attributes['confirmation'] = Registry::load('strings')->confirm_action;
            $index++;
        }


        $i++;
    }
}
?>