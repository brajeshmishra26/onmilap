<?php

$video_log_data = data_cache(['folder' => 'private_video_call_logs', 'filename' => $current_user_id, 'method' => 'get']);

if (empty($video_log_data)) {
    $video_log_data = data_cache(['folder' => 'private_audio_call_logs', 'filename' => $current_user_id, 'method' => 'get']);
}

$caller_id = 0;

$data["current_call_id"] = filter_var($data["current_call_id"], FILTER_SANITIZE_NUMBER_INT);

if (is_array($video_log_data) && isset($video_log_data['incoming'])) {
    if (!isset($video_log_data['accepted'])) {
        $result['new_call_notification'] = $video_log_data;
        $caller_id = $video_log_data['caller_id'];
    }
}

if ((int)$data["current_call_id"] !== (int)$caller_id) {
    $escape = true;
}