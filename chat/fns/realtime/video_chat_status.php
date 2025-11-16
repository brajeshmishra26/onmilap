<?php

if (isset($data["video_chat_status"]) && $data["video_chat_status"] === 'online') {
    $current_video_chat_online = true;
} else {
    $current_video_chat_online = false;
}

if (isset($data["audio_chat_status"]) && $data["audio_chat_status"] === 'online') {
    $current_audio_chat_online = true;
} else {
    $current_audio_chat_online = false;
}

$video_online_status = false;
$result['video_chat_status'] = array();
$video_log_data = array();
$find_by_id = null;
$video_log_exists = true;
$vc_log_folder = null;

if (isset($data['group_id'])) {
    $data["group_id"] = filter_var($data["group_id"], FILTER_SANITIZE_NUMBER_INT);

    if (!empty($data["group_id"])) {
        $video_log_data = data_cache(['folder' => 'group_video_call_logs', 'filename' => $data["group_id"], 'method' => 'get']);
        $vc_log_folder = 'group_video_call_logs';

        if (empty($video_log_data)) {
            $video_log_exists = false;
            $video_log_data = data_cache(['folder' => 'group_audio_call_logs', 'filename' => $data["group_id"], 'method' => 'get']);
            $vc_log_folder = 'group_audio_call_logs';
        }

        $find_by_id = $data["group_id"];

        $result['video_chat_status']['group_id'] = $data['group_id'];


    }
} else if (isset($data['user_id'])) {
    $data["user_id"] = filter_var($data["user_id"], FILTER_SANITIZE_NUMBER_INT);

    if (!empty($data["user_id"])) {

        $result['video_chat_status']['user_id'] = $data['user_id'];

        $video_log_data = data_cache(['folder' => 'private_video_call_logs', 'filename' => $data["user_id"], 'method' => 'get']);
        $vc_log_folder = 'private_video_call_logs';
        $find_by_id = $data["user_id"];

        if (empty($video_log_data)) {
            $video_log_data = data_cache(['folder' => 'private_audio_call_logs', 'filename' => $data["user_id"], 'method' => 'get']);
            $vc_log_folder = 'private_audio_call_logs';
        }

        if (isset($data['current_video_caller_id']) && isset($data['current_video_caller_id'])) {

            if (isset($data['audio_only_call'])) {
                $call_log = 'private_audio_call_logs';
            } else {
                $call_log = 'private_video_call_logs';
            }

            $check_video_call_log = data_cache(['folder' => $call_log, 'filename' => $data["current_video_caller_id"], 'method' => 'get']);

            if (!isset($check_video_call_log['caller_id'])) {
                $result['video_chat_status']['rejected'] = true;
            }

        }

    }
}

if (!empty($video_log_data)) {

    if (isset($data['group_id']) || isset($video_log_data['caller_id']) && (int)$video_log_data['caller_id'] === (int)$current_user_id) {

        if (isset($video_log_data['last_updated_on'])) {
            $lastUpdatedTimestamp = strtotime($video_log_data['last_updated_on']);
            $currentTimestamp = strtotime(Registry::load('current_user')->time_stamp);

            $timeDifference = $currentTimestamp - $lastUpdatedTimestamp;

            if ($timeDifference > 60) {
                unset($video_log_data['online']);

                if (!empty($vc_log_folder)) {
                    data_cache(['folder' => $vc_log_folder, 'filename' => $find_by_id, 'method' => 'delete']);
                }

                if (isset($data['user_id'])) {
                    if (!empty($vc_log_folder)) {
                        data_cache(['folder' => $vc_log_folder, 'filename' => $current_user_id, 'method' => 'delete']);
                    }
                }
            }
        }

        if (isset($video_log_data['online']) && $video_log_data['online']) {
            $video_online_status = true;
        }
    }

}


if (isset($data['call_rejected'])) {
    $video_online_status = false;
}



if ($current_video_chat_online && !$video_log_exists) {
    $escape = true;
} else {

    if ($video_online_status) {
        $result['video_chat_status']['online'] = true;
    }

    if (isset($video_log_data['audio_only'])) {
        $result['video_chat_status']['audio_only'] = true;

        if ($video_online_status !== $current_audio_chat_online) {
            $escape = true;
        }

    } else if ($current_audio_chat_online && !isset($video_log_data['audio_only'])) {
        $result['video_chat_status']['audio_only'] = true;
        $escape = true;
    } else {
        if ($video_online_status !== $current_video_chat_online) {
            $escape = true;
        }
    }
}