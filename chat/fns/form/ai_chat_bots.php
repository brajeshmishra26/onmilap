<?php

if (role(['permissions' => ['super_privileges' => 'core_settings']])) {

    $form = array();

    $todo = 'add';
    $location = 0;
    $form['loaded'] = new stdClass();
    $form['fields'] = new stdClass();

    if (isset($load["ai_chat_bot_id"])) {
        $load["ai_chat_bot_id"] = filter_var($load["ai_chat_bot_id"], FILTER_SANITIZE_NUMBER_INT);
    }

    if (isset($load["ai_chat_bot_id"]) && !empty($load["ai_chat_bot_id"])) {

        $columns = $where = $join = null;

        $columns = [
            'site_users.user_id', 'site_users.display_name', 'ai_chat_bots.description',
            'site_users.username', 'ai_chat_bots.ai_chat_bot_id'
        ];

        $where = [
            "ai_chat_bots.ai_chat_bot_id" => $load["ai_chat_bot_id"],
            "LIMIT" => 1
        ];
        $join["[>]site_users"] = ["ai_chat_bots.user_id" => "user_id"];
        $ai_chat_bot = DB::connect()->select('ai_chat_bots', $join, $columns, $where);

        if (isset($ai_chat_bot[0])) {
            $ai_chat_bot = $ai_chat_bot[0];
        } else {
            return;
        }

        $todo = 'update';
        $form['fields']->ai_chat_bot_id = [
            "tag" => 'input', "type" => 'hidden', "class" => 'd-none', "value" => $load["ai_chat_bot_id"]
        ];

        $form['loaded']->title = Registry::load('strings')->ai_chat_bots;
        $form['loaded']->button = Registry::load('strings')->update;


        $form['fields']->name = [
            "title" => Registry::load('strings')->name, "tag" => 'input', "type" => "text", "class" => 'field',
            "value" => $ai_chat_bot['display_name'], "attributes" => ["disabled" => true]
        ];

    } else {
        $form['loaded']->title = Registry::load('strings')->ai_chat_bots;
        $form['loaded']->button = Registry::load('strings')->create;
    }

    $form['fields']->$todo = [
        "tag" => 'input', "type" => 'hidden', "class" => 'd-none', "value" => "ai_chat_bots"
    ];

    $form['fields']->username = [
        "title" => Registry::load('strings')->username, "tag" => 'input', "type" => "text", "class" => 'field',
        "placeholder" => Registry::load('strings')->username,
    ];


    $form['fields']->description = [
        "title" => Registry::load('strings')->describe_bot, "tag" => 'textarea', "class" => 'field',
        "placeholder" => Registry::load('strings')->describe_bot,
    ];

    $form['fields']->description["attributes"] = ["rows" => 10];

    if (isset($load["ai_chat_bot_id"]) && !empty($load["ai_chat_bot_id"])) {
        $form['fields']->username['value'] = $ai_chat_bot['username'];
        $form['fields']->description['value'] = $ai_chat_bot['description'];
    }

}
?>