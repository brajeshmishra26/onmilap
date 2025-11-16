<?php

$result = array();
$result['success'] = false;
$result['error_message'] = Registry::load('strings')->went_wrong;
$result['error_key'] = 'something_went_wrong';

if (role(['permissions' => ['super_privileges' => 'core_settings']])) {

    $noerror = true;
    $ai_chat_bot_id = $user_id = 0;

    $result['success'] = false;
    $result['error_message'] = Registry::load('strings')->invalid_value;
    $result['error_key'] = 'invalid_value';
    $result['error_variables'] = [];

    if (!isset($data['username']) || empty($data['username'])) {
        $result['error_variables'][] = ['username'];
        $noerror = false;
    }
    if (!isset($data['description']) || empty($data['description'])) {
        $result['error_variables'][] = ['description'];
        $noerror = false;
    }

    if (isset($data['ai_chat_bot_id'])) {
        $ai_chat_bot_id = filter_var($data["ai_chat_bot_id"], FILTER_SANITIZE_NUMBER_INT);
    }

    if (isset($data['username']) && !empty($data['username'])) {
        $result['error_message'] = Registry::load('strings')->invalid_value;
        $result['error_key'] = 'invalid_value';
        $result['error_variables'][] = ['username'];
        $noerror = false;

        $site_user = DB::connect()->select('site_users', ['user_id'], ['username' => $data['username'], 'LIMIT' => 1]);

        if (isset($site_user[0])) {
            $noerror = true;
            $user_id = $site_user[0]['user_id'];
        }
    }

    if ($noerror && !empty($ai_chat_bot_id)) {

        $data['description'] = htmlspecialchars(trim($data['description']), ENT_QUOTES, 'UTF-8');
        
        DB::connect()->update("ai_chat_bots", [
            "description" => $data['description'],
            "user_id" => $user_id,
            "updated_on" => Registry::load('current_user')->time_stamp,
        ], ["ai_chat_bot_id" => $ai_chat_bot_id]);

        if (!DB::connect()->error) {
            $result = array();
            $result['success'] = true;
            $result['todo'] = 'reload';
            $result['reload'] = 'ai_chat_bots';
        } else {
            $result['error_message'] = Registry::load('strings')->went_wrong;
            $result['error_key'] = 'something_went_wrong';
        }

    }
}

?>