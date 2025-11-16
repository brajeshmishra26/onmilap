<?php

if (role(['permissions' => ['super_privileges' => 'core_settings']])) {

    $user_id = Registry::load('current_user')->id;
    $columns = $where = $join = null;

    $columns = [
        'site_users.user_id', 'site_users.display_name', 'site_users.email_address',
        'site_users.username', 'site_users.profile_picture', 'ai_chat_bots.ai_chat_bot_id'
    ];

    if (!empty($data["offset"])) {
        $data["offset"] = array_map('intval', explode(',', $data["offset"]));
        $where["ai_chat_bots.ai_chat_bot_id[!]"] = $data["offset"];
    }

    if (!empty($data["search"])) {
        $where["AND #search_query"]["OR"] = ["site_users.display_name[~]" => $data["search"], "site_users.username[~]" => $data["search"]];
    }

    $where["LIMIT"] = Registry::load('settings')->records_per_call;

    if ($data["sortby"] === 'name_asc') {
        $where["ORDER"] = ["site_users.display_name" => "ASC"];
    } else if ($data["sortby"] === 'name_desc') {
        $where["ORDER"] = ["site_users.display_name" => "DESC"];
    } else {
        $where["ORDER"] = ["ai_chat_bots.ai_chat_bot_id" => "DESC"];
    }

    $join["[>]site_users"] = ["ai_chat_bots.user_id" => "user_id"];
    $ai_chat_bots = DB::connect()->select('ai_chat_bots', $join, $columns, $where);

    $i = 1;
    $output = array();
    $output['loaded'] = new stdClass();
    $output['loaded']->title = Registry::load('strings')->ai_chat_bots;
    $output['loaded']->offset = array();

    if (!empty($data["offset"])) {
        $output['loaded']->offset = $data["offset"];
    }

    $output['multiple_select'] = new stdClass();
    $output['multiple_select']->attributes['class'] = 'ask_confirmation';
    $output['multiple_select']->attributes['multi_select'] = 'ai_chat_bot_id';
    $output['multiple_select']->attributes['submit_button'] = Registry::load('strings')->yes;
    $output['multiple_select']->attributes['cancel_button'] = Registry::load('strings')->no;
    $output['multiple_select']->attributes['confirmation'] = Registry::load('strings')->confirm_action;
    $output['multiple_select']->title = Registry::load('strings')->remove;
    $output['multiple_select']->attributes['data-remove'] = 'ai_chat_bots';

    $output['todo'] = new stdClass();
    $output['todo']->class = 'load_form';
    $output['todo']->title = Registry::load('strings')->add_users;
    $output['todo']->attributes['form'] = 'ai_chat_bots';


    foreach ($ai_chat_bots as $user) {

        $output['loaded']->offset[] = $user['ai_chat_bot_id'];

        $output['content'][$i] = new stdClass();
        $output['content'][$i]->image = get_img_url(['from' => 'site_users/profile_pics', 'image' => $user['profile_picture'], 'gravatar' => $user['email_address']]);
        $output['content'][$i]->title = $user['display_name'];
        $output['content'][$i]->class = "ai_chat_bots";
        $output['content'][$i]->icon = 0;
        $output['content'][$i]->unread = 0;
        $output['content'][$i]->identifier = $user['ai_chat_bot_id'];

        $output['content'][$i]->subtitle = $user['username'];

        $option_index = 1;

        $output['options'][$i][$option_index] = new stdClass();
        $output['options'][$i][$option_index]->option = Registry::load('strings')->edit;
        $output['options'][$i][$option_index]->class = 'load_form';
        $output['options'][$i][$option_index]->attributes['form'] = 'ai_chat_bots';
        $output['options'][$i][$option_index]->attributes['data-ai_chat_bot_id'] = $user['ai_chat_bot_id'];
        $option_index++;

        $output['options'][$i][$option_index] = new stdClass();
        $output['options'][$i][$option_index]->option = Registry::load('strings')->remove;
        $output['options'][$i][$option_index]->class = 'ask_confirmation';
        $output['options'][$i][$option_index]->attributes['data-remove'] = 'ai_chat_bots';
        $output['options'][$i][$option_index]->attributes['data-ai_chat_bot_id'] = $user['ai_chat_bot_id'];
        $output['options'][$i][$option_index]->attributes['confirmation'] = Registry::load('strings')->confirm_action;
        $output['options'][$i][$option_index]->attributes['submit_button'] = Registry::load('strings')->yes;
        $output['options'][$i][$option_index]->attributes['cancel_button'] = Registry::load('strings')->no;
        $option_index++;


        $output['options'][$i][$option_index] = new stdClass();
        $output['options'][$i][$option_index]->option = Registry::load('strings')->profile;
        $output['options'][$i][$option_index]->class = 'get_info';
        $output['options'][$i][$option_index]->attributes['user_id'] = $user['user_id'];

        $i++;
    }
}
?>