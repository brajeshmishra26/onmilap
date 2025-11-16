<?php

if (isset($data["user_id"])) {
    $data["user_id"] = filter_var($data["user_id"], FILTER_SANITIZE_NUMBER_INT);
}

$current_user_id = Registry::load('current_user')->id;

$output = array();
$output['loaded'] = new stdClass();
$output['loaded']->format = 'list';
$output['loaded']->offset = array();
$output['loaded']->title = Registry::load('strings')->other_files;

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

    $where["private_chat_messages.attachment_type"] = 'other_files';


    $where["ORDER"] = ['private_chat_messages.private_chat_message_id' => 'DESC'];
    $where["LIMIT"] = 10;



    $other_files = DB::connect()->select('private_chat_messages', $columns, $where);

    $index = 0;

    if (!empty($data["offset"])) {
        $output['loaded']->offset = $data["offset"];
    }


    if (count($other_files) < 10) {
        unset($output['loaded']->load_more);
    }

    foreach ($other_files as $other_file) {

        $attachments = json_decode($other_file['attachments']);
        $output['loaded']->offset[] = $other_file['private_chat_message_id'];

        foreach ($attachments as $attachment_index => $attachment) {
            if (isset($attachment->file)) {
                $file_icon = mb_strtolower(pathinfo($attachment->trimmed_name, PATHINFO_EXTENSION));
                $file_icon = "assets/files/file_extensions/".$file_icon.".png";
                $output['content'][$index] = new stdClass();
                $output['content'][$index]->image = Registry::load('config')->site_url."assets/files/file_extensions/unknown.png";

                if (file_exists($file_icon)) {
                    $output['content'][$index]->image = Registry::load('config')->site_url.$file_icon;
                }

                $output['content'][$index]->attributes = [
                    'class' => 'download_file',
                    'download' => 'attachment',
                    'data-private_conversation_id' => $private_conversation["private_conversation_id"],
                    'data-message_id' => $other_file['private_chat_message_id'],
                    'data-attachment_index' => $attachment_index,
                ];

                if (isset(Registry::load('settings')->display_full_file_name_of_attachments) && Registry::load('settings')->display_full_file_name_of_attachments === 'yes') {
                    $output['content'][$index]->heading = $attachment->name;
                } else {
                    $output['content'][$index]->heading = $attachment->trimmed_name;
                }
                $output['content'][$index]->description = $attachment->file_size;

            }

            $index++;
        }
    }

}