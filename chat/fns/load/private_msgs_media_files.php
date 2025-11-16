<?php

if (isset($data["user_id"])) {
    $data["user_id"] = filter_var($data["user_id"], FILTER_SANITIZE_NUMBER_INT);
}

$current_user_id = Registry::load('current_user')->id;

$output = array();
$output['loaded'] = new stdClass();
$output['loaded']->format = 'grid';
$output['loaded']->offset = array();
$output['loaded']->title = Registry::load('strings')->media;

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
    
    $where["AND"]["OR #fourth condition"] = [
        "private_chat_messages.attachment_type(image_files)" => 'image_files',
        "private_chat_messages.attachment_type(video_files)" => 'video_files',
        "private_chat_messages.attachment_type(audio_files)" => 'audio_files'
    ];


    $where["ORDER"] = ['private_chat_messages.private_chat_message_id' => 'DESC'];
    $where["LIMIT"] = 10;


    $media_files = DB::connect()->select('private_chat_messages', $columns, $where);

    $index = 0;

    if (!empty($data["offset"])) {
        $output['loaded']->offset = $data["offset"];
    }


    if (count($media_files) < 10) {
        unset($output['loaded']->load_more);
    }

    foreach ($media_files as $media_file) {

        $attachments = json_decode($media_file['attachments']);
        $output['loaded']->offset[] = $media_file['private_chat_message_id'];

        foreach ($attachments as $attachment) {
            if (file_exists($attachment->file) || Registry::load('settings')->cloud_storage !== 'disable') {
                if ($media_file['attachment_type'] === 'image_files' && isset($attachment->thumbnail)) {
                    $output['content'][$index] = new stdClass();
                    $output['content'][$index]->image = Registry::load('config')->site_url.'assets/files/defaults/image_thumb.jpg';

                    if (file_exists($attachment->thumbnail) || Registry::load('settings')->cloud_storage !== 'disable') {
                        $output['content'][$index]->image = $storage_public_url.$attachment->thumbnail;
                    }

                    $output['content'][$index]->attributes = [
                        'class' => 'preview_image',
                        'load_image' => $storage_public_url.$attachment->file,
                    ];

                } else if ($media_file['attachment_type'] === 'video_files' && isset($attachment->file)) {
                    $output['content'][$index] = new stdClass();
                    $output['content'][$index]->image = Registry::load('config')->site_url.'assets/files/defaults/video_thumb.jpg';

                    if (isset($attachment->thumbnail) && file_exists($attachment->thumbnail) || isset($attachment->thumbnail) && Registry::load('settings')->cloud_storage !== 'disable') {
                        $output['content'][$index]->image = $storage_public_url.$attachment->thumbnail;
                    }

                    $output['content'][$index]->attributes = [
                        'class' => 'preview_video',
                        'mime_type' => $attachment->file_type,
                        'thumbnail' => $output['content'][$index]->image,
                        'video_file' => $storage_public_url.$attachment->file,
                    ];


                } else if ($media_file['attachment_type'] === 'audio_files') {
                    $output['content'][$index] = new stdClass();
                    $output['content'][$index]->image = Registry::load('config')->site_url.'assets/files/defaults/audio_thumb.jpg';

                    $output['content'][$index]->attributes = [
                        'class' => 'preview_video',
                        'thumbnail' => $output['content'][$index]->image,
                        'mime_type' => $attachment->file_type,
                        'video_file' => $storage_public_url.$attachment->file,
                    ];

                }
            }

            $index++;
        }
    }

}