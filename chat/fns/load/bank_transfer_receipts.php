<?php

if (role(['permissions' => ['bank_transfer_receipts' => 'view']])) {

    $access_old_receipts = false;

    $columns = [
        'wallet_bank_receipts.bank_transfer_receipt_id', 'wallet_bank_receipts.wallet_transaction_id',
        'wallet_bank_receipts.receipt_status', 'wallet_bank_receipts.created_on', 'site_users.display_name',
    ];

    $join["[>]site_users_wallet"] = ["wallet_bank_receipts.wallet_transaction_id" => "wallet_transaction_id"];
    $join["[>]site_users"] = ["site_users_wallet.user_id" => "user_id"];

    if (!empty($data["offset"])) {
        $data["offset"] = array_map('intval', explode(',', $data["offset"]));
        $where["wallet_bank_receipts.bank_transfer_receipt_id[!]"] = $data["offset"];
    }

    if (!empty($data["search"])) {

        $id_search = filter_var($data["search"], FILTER_SANITIZE_NUMBER_INT);

        if (empty($id_search)) {
            $id_search = 0;
        }

        $where["AND #search_query"]["OR"] = [
            "wallet_bank_receipts.wallet_transaction_id[~]" => $id_search,
            "site_users.display_name[~]" => $data["search"]
        ];
    }

    $where["LIMIT"] = Registry::load('settings')->records_per_call;

    if ($data["sortby"] === 'status_asc') {
        $where["ORDER"] = ["wallet_bank_receipts.receipt_status" => "ASC"];
    } else if ($data["sortby"] === 'status_desc') {
        $where["ORDER"] = ["wallet_bank_receipts.receipt_status" => "DESC"];
    } else {
        $where["ORDER"] = ["wallet_bank_receipts.bank_transfer_receipt_id" => "DESC"];
    }

    $bank_transfer_receipts = DB::connect()->select('wallet_bank_receipts', $join, $columns, $where);

    $i = 1;
    $output = array();
    $output['loaded'] = new stdClass();
    $output['loaded']->title = Registry::load('strings')->bank_receipts;
    $output['loaded']->loaded = 'bank_transfer_receipts';
    $output['loaded']->offset = array();

    if ($access_old_receipts) {
        $output['filters'][1] = new stdClass();
        $output['filters'][1]->filter = Registry::load('strings')->all;
        $output['filters'][1]->class = 'load_aside';
        $output['filters'][1]->attributes['load'] = 'bank_transfer_receipts';

        $output['filters'][2] = new stdClass();
        $output['filters'][2]->filter = Registry::load('strings')->old_receipts;
        $output['filters'][2]->class = 'load_aside';
        $output['filters'][2]->attributes['load'] = 'old_bank_receipts';
        $output['filters'][2]->attributes['filter'] = 'old_receipts';
        $output['filters'][2]->attributes['skip_filter_title'] = true;
    }


    if (role(['permissions' => ['bank_transfer_receipts' => 'delete']])) {
        $output['multiple_select'] = new stdClass();
        $output['multiple_select']->title = Registry::load('strings')->delete;
        $output['multiple_select']->attributes['class'] = 'ask_confirmation';
        $output['multiple_select']->attributes['data-remove'] = 'bank_transfer_receipts';
        $output['multiple_select']->attributes['multi_select'] = 'bank_transfer_receipt_id';
        $output['multiple_select']->attributes['submit_button'] = Registry::load('strings')->yes;
        $output['multiple_select']->attributes['cancel_button'] = Registry::load('strings')->no;
        $output['multiple_select']->attributes['confirmation'] = Registry::load('strings')->confirm_action;
    }

    $output['sortby'][1] = new stdClass();
    $output['sortby'][1]->sortby = Registry::load('strings')->sort_by_default;
    $output['sortby'][1]->class = 'load_aside';
    $output['sortby'][1]->attributes['load'] = 'bank_transfer_receipts';

    $output['sortby'][2] = new stdClass();
    $output['sortby'][2]->sortby = Registry::load('strings')->status;
    $output['sortby'][2]->class = 'load_aside sort_asc';
    $output['sortby'][2]->attributes['load'] = 'bank_transfer_receipts';
    $output['sortby'][2]->attributes['sort'] = 'status_asc';

    $output['sortby'][3] = new stdClass();
    $output['sortby'][3]->sortby = Registry::load('strings')->status;
    $output['sortby'][3]->class = 'load_aside sort_desc';
    $output['sortby'][3]->attributes['load'] = 'bank_transfer_receipts';
    $output['sortby'][3]->attributes['sort'] = 'status_desc';


    if (!empty($data["offset"])) {
        $output['loaded']->offset = $data["offset"];
    }

    foreach ($bank_transfer_receipts as $bank_receipt) {
        $output['loaded']->offset[] = $bank_receipt['bank_transfer_receipt_id'];
        $output['content'][$i] = new stdClass();
        $output['content'][$i]->image = Registry::load('config')->site_url."assets/files/defaults/bank_receipt.png";
        $output['content'][$i]->identifier = $bank_receipt['bank_transfer_receipt_id'];
        $output['content'][$i]->title = Registry::load('strings')->id.': '.$bank_receipt['wallet_transaction_id'];
        $output['content'][$i]->title .= ' - '.$bank_receipt['display_name'];
        $output['content'][$i]->class = "receipt";
        $output['content'][$i]->icon = 0;
        $output['content'][$i]->unread = 0;


        if ((int)$bank_receipt['receipt_status'] === 0) {
            $output['content'][$i]->image = Registry::load('config')->site_url."assets/files/defaults/bank_receipt_pending.png";
            $output['content'][$i]->subtitle = Registry::load('strings')->pending;

        } else if ((int)$bank_receipt['receipt_status'] === 1) {

            $output['content'][$i]->image = Registry::load('config')->site_url."assets/files/defaults/bank_receipt_accepted.png";
            $output['content'][$i]->subtitle = Registry::load('strings')->accepted;

        } else if ((int)$bank_receipt['receipt_status'] === 2) {

            $output['content'][$i]->image = Registry::load('config')->site_url."assets/files/defaults/bank_receipt_rejected.png";
            $output['content'][$i]->subtitle = Registry::load('strings')->rejected;
        }


        $output['options'][$i][1] = new stdClass();
        $output['options'][$i][1]->option = Registry::load('strings')->view;
        $output['options'][$i][1]->class = 'load_form';
        $output['options'][$i][1]->attributes['form'] = 'bank_transfer_receipts';
        $output['options'][$i][1]->attributes['data-bank_transfer_receipt_id'] = $bank_receipt['bank_transfer_receipt_id'];

        if (role(['permissions' => ['bank_transfer_receipts' => 'validate']])) {
            $output['options'][$i][1]->option = Registry::load('strings')->validate;
        }

        if (role(['permissions' => ['bank_transfer_receipts' => 'delete']])) {
            $output['options'][$i][2] = new stdClass();
            $output['options'][$i][2]->option = Registry::load('strings')->delete;
            $output['options'][$i][2]->class = 'ask_confirmation';
            $output['options'][$i][2]->attributes['data-remove'] = 'bank_transfer_receipts';
            $output['options'][$i][2]->attributes['data-bank_transfer_receipt_id'] = $bank_receipt['bank_transfer_receipt_id'];
            $output['options'][$i][2]->attributes['submit_button'] = Registry::load('strings')->yes;
            $output['options'][$i][2]->attributes['cancel_button'] = Registry::load('strings')->no;
            $output['options'][$i][2]->attributes['confirmation'] = Registry::load('strings')->confirm_action;
        }

        $i++;
    }
}
?>