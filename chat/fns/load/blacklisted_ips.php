<?php

if (role(['permissions' => ['super_privileges' => 'firewall']])) {


    $columns = [
        'blacklist_ip_id', 'ip_address', 'created_on'
    ];

    if (!empty($data["offset"])) {
        $data["offset"] = array_map('intval', explode(',', $data["offset"]));
        $where["blacklist_ip_id[!]"] = $data["offset"];
    }

    if (!empty($data["search"])) {
        $where["AND #search_query"] = ["ip_address[~]" => $data["search"]];
    }


    $where["LIMIT"] = Registry::load('settings')->records_per_call;
    $where["ORDER"] = [
        "blacklist_ip_id" => "DESC",
    ];

    $blacklisted_ips = DB::connect()->select('blacklisted_ips', $columns, $where);

    $i = 1;
    $output = array();
    $output['loaded'] = new stdClass();
    $output['loaded']->title = Registry::load('strings')->ip_blacklist;
    $output['loaded']->loaded = 'blacklisted_ips';
    $output['loaded']->offset = array();

    if (!empty($data["offset"])) {
        $output['loaded']->offset = $data["offset"];
    }


    $output['multiple_select'] = new stdClass();
    $output['multiple_select']->title = Registry::load('strings')->delete;
    $output['multiple_select']->attributes['class'] = 'ask_confirmation';
    $output['multiple_select']->attributes['data-remove'] = 'blacklisted_ips';
    $output['multiple_select']->attributes['multi_select'] = 'blacklist_ip_id';
    $output['multiple_select']->attributes['submit_button'] = Registry::load('strings')->yes;
    $output['multiple_select']->attributes['cancel_button'] = Registry::load('strings')->no;
    $output['multiple_select']->attributes['confirmation'] = Registry::load('strings')->confirm_action;

    $output['todo'] = new stdClass();
    $output['todo']->class = 'load_form';
    $output['todo']->title = Registry::load('strings')->firewall;
    $output['todo']->attributes['form'] = 'firewall';


    foreach ($blacklisted_ips as $blacklisted_ip) {
        $output['loaded']->offset[] = $blacklisted_ip['blacklist_ip_id'];

        $output['content'][$i] = new stdClass();
        $output['content'][$i]->image = Registry::load('config')->site_url."assets/files/defaults/access_log.png";
        $output['content'][$i]->title = $blacklisted_ip['ip_address'];
        $output['content'][$i]->identifier = $blacklisted_ip['blacklist_ip_id'];
        $output['content'][$i]->class = "blacklisted_ip square";
        $output['content'][$i]->icon = 0;
        $output['content'][$i]->unread = 0;

        $created_on = array();
        $created_on['date'] = $blacklisted_ip['created_on'];
        $created_on['auto_format'] = true;
        $created_on['include_time'] = true;
        $created_on['timezone'] = Registry::load('current_user')->time_zone;
        $created_on = get_date($created_on);

        $output['content'][$i]->subtitle = $created_on['date'].' '.$created_on['time'];


        $output['options'][$i][3] = new stdClass();
        $output['options'][$i][3]->option = Registry::load('strings')->delete;
        $output['options'][$i][3]->class = 'ask_confirmation';
        $output['options'][$i][3]->attributes['data-info_box'] = true;
        $output['options'][$i][3]->attributes['data-remove'] = 'blacklisted_ips';
        $output['options'][$i][3]->attributes['data-blacklist_ip_id'] = $blacklisted_ip['blacklist_ip_id'];
        $output['options'][$i][3]->attributes['confirmation'] = Registry::load('strings')->confirm_action;
        $output['options'][$i][3]->attributes['submit_button'] = Registry::load('strings')->yes;
        $output['options'][$i][3]->attributes['cancel_button'] = Registry::load('strings')->no;


        $i++;
    }
}
?>