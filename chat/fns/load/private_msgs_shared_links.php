<?php

if (isset($data["user_id"])) {
    $data["user_id"] = filter_var($data["user_id"], FILTER_SANITIZE_NUMBER_INT);
}

$current_user_id = Registry::load('current_user')->id;

$output = array();
$output['loaded'] = new stdClass();
$output['loaded']->format = 'list';
$output['loaded']->offset = array();
$output['loaded']->title = Registry::load('strings')->links;

$storage_public_url = Registry::load('config')->site_url;

if (Registry::load('settings')->cloud_storage !== 'disable') {
    if (!empty(Registry::load('settings')->cloud_storage_public_url)) {
        $storage_public_url = Registry::load('settings')->cloud_storage_public_url;
    }
}

if (isset($data["user_id"]) && !empty($data["user_id"])) {
    $columns = $join = $where = null;
    $columns = [
        'private_conversations.private_conversation_id',
        'private_conversations.initiator_user_id', 'private_conversations.recipient_user_id',
        'private_conversations.initiator_load_message_id_from', 'private_conversations.recipient_load_message_id_from'
    ];

    $where["OR"]["AND #first_query"] = [
        "private_conversations.initiator_user_id" => $data["user_id"],
        "private_conversations.recipient_user_id" => $current_user_id,
    ];
    $where["OR"]["AND #second_query"] = [
        "private_conversations.initiator_user_id" => $current_user_id,
        "private_conversations.recipient_user_id" => $data["user_id"],
    ];

    $where["LIMIT"] = 1;
    $private_conversation = DB::connect()->select('private_conversations', $columns, $where);

    if (isset($private_conversation[0])) {
        $private_conversation = $private_conversation[0];
    } else {
        return;
    }

    $output['loaded']->load_more = true;

    $columns = $join = $where = null;
    $columns = [
        'private_chat_messages.private_chat_message_id', 'private_chat_messages.attachments', 'private_chat_messages.attachment_type'
    ];

    $where["private_chat_messages.private_conversation_id"] = $private_conversation["private_conversation_id"];

    if (isset($data["offset"])) {
        if (!empty($data["offset"])) {
            $data["offset"] = array_map('intval', explode(',', $data["offset"]));
            $where["private_chat_messages.private_chat_message_id[!]"] = $data["offset"];
        }
    }

    $where["private_chat_messages.attachment_type"] = 'url_meta';


    $where["ORDER"] = ['private_chat_messages.private_chat_message_id' => 'DESC'];
    $where["LIMIT"] = 10;



    $shared_links = DB::connect()->select('private_chat_messages', $columns, $where);

    $index = 0;

    if (!empty($data["offset"])) {
        $output['loaded']->offset = $data["offset"];
    }


    if (count($shared_links) < 10) {
        unset($output['loaded']->load_more);
    }

    foreach ($shared_links as $shared_link) {

        $attachments = json_decode($shared_link['attachments']);
        $output['loaded']->offset[] = $shared_link['private_chat_message_id'];

        if (isset($attachments->title)) {

            $output['content'][$index] = new stdClass();
            $output['content'][$index]->image = Registry::load('config')->site_url.'assets/files/defaults/video_thumb.jpg';

            if (!empty($attachments->image)) {
                $output['content'][$index]->image = $attachments->image;
            }

            $output['content'][$index]->attributes = [
                'class' => 'open_link',
                'link' => $attachments->url,
                'target' => '_blank',
            ];


            $output['content'][$index]->heading = $attachments->title;
            $output['content'][$index]->description = $attachments->description;

            $index++;
        }
    }

}