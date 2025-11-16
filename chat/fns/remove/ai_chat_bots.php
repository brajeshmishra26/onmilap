<?php
$result = array();
$noerror = true;

$result['success'] = false;
$result['error_message'] = Registry::load('strings')->went_wrong;
$result['error_key'] = 'something_went_wrong';
$ai_chat_bot_ids = array();

if (role(['permissions' => ['super_privileges' => 'core_settings']])) {

    if (isset($data['ai_chat_bot_id'])) {
        if (!is_array($data['ai_chat_bot_id'])) {
            $data["ai_chat_bot_id"] = filter_var($data["ai_chat_bot_id"], FILTER_SANITIZE_NUMBER_INT);
            $ai_chat_bot_ids[] = $data["ai_chat_bot_id"];
        } else {
            $ai_chat_bot_ids = array_filter($data["ai_chat_bot_id"], 'ctype_digit');
        }
    }

    if (isset($data['ai_chat_bot_id']) && !empty($data['ai_chat_bot_id'])) {

        DB::connect()->delete("ai_chat_bots", ["ai_chat_bot_id" => $ai_chat_bot_ids]);

        if (!DB::connect()->error) {
            $result = array();
            $result['success'] = true;
            $result['todo'] = 'reload';
            $result['reload'] = 'ai_chat_bots';
        } else {
            $result['errormsg'] = Registry::load('strings')->went_wrong;
        }
    }
}
?>