<?php

function ai_chat_module() {
    $provider = Registry::load('settings')->ai_chat_bots;
    $function_file = '';
    $result = array();
    $result['success'] = false;
    $result['error_key'] = 'something_went_wrong';
    $receiver_id = 0;

    if ($provider !== 'disable') {

        if (!empty($provider)) {
            $provider = preg_replace("/[^a-zA-Z0-9_]+/", "", $provider);
            $provider = str_replace('libraries', '', $provider);
        }

        if (!empty($provider)) {
            $function_file = 'fns/ai_chat/'.$provider.'.php';
            if (file_exists($function_file)) {


                $columns = $join = $where = $previous_chat_msgs = null;

                $columns = [
                    'ai_chat_messages.ai_msg_id',
                    'ai_chat_messages.private_conversation_id',
                    'ai_chat_messages.private_chat_message_id',
                    'ai_chat_bots.user_id',
                    'ai_chat_bots.description'
                ];

                $join["[>]ai_chat_bots"] = ["ai_chat_messages.ai_chat_bot_id" => "ai_chat_bot_id"];


                $where["LIMIT"] = 5;

                $ai_chat_msgs = DB::connect()->select('ai_chat_messages', $join, $columns, $where);
                foreach ($ai_chat_msgs as $ai_chat_msg) {

                    DB::connect()->delete('ai_chat_messages', [
                        'ai_msg_id' => $ai_chat_msg['ai_msg_id']
                    ]);


                    $previous_chat_msgs = DB::connect()->select('private_chat_messages', ['filtered_message', 'user_id'], [
                        'private_chat_message_id[<=]' => $ai_chat_msg['private_chat_message_id'],
                        'ORDER' => ['private_chat_message_id' => 'DESC'],
                        'LIMIT' => 8
                    ]);

                    if (!empty($previous_chat_msgs)) {
                        $messages = array();

                        $messages[] = ['role' => 'system', 'content' => $ai_chat_msg['description']];

                        $previous_chat_msgs = array_reverse($previous_chat_msgs);

                        foreach ($previous_chat_msgs as $previous_chat_msg) {
                            $filtered_message = $previous_chat_msg['filtered_message'];
                            $filtered_message = strip_tags($filtered_message);

                            if ((int)$previous_chat_msg['user_id'] === (int)$ai_chat_msg['user_id']) {
                                $messages[] = ['role' => 'assistant', 'content' => $filtered_message];
                            } else {
                                $messages[] = ['role' => 'user', 'content' => $filtered_message];
                                $receiver_id = $previous_chat_msg['user_id'];
                            }
                        }
                        include($function_file);
                    }
                }
            }
        }
    }

    return $result;

}