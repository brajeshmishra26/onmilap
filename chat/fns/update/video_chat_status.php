<?php

if (Registry::load('current_user')->logged_in) {
    if (Registry::load('settings')->video_chat !== 'disable') {

        if (isset($data['group_id'])) {
            $data["group_id"] = filter_var($data["group_id"], FILTER_SANITIZE_NUMBER_INT);

            if (!empty($data["group_id"])) {

                if (isset($data["audio_only_chat"])) {
                    $call_log_folder = 'group_audio_call_logs';
                } else {
                    $call_log_folder = 'group_video_call_logs';
                }

                if (isset($data["offline"])) {
                    if (isset($data["total_vc_users"]) && empty($data["total_vc_users"])) {
                        data_cache(['folder' => $call_log_folder, 'filename' => $data["group_id"], 'method' => 'delete']);
                        ws_push(['update' => 'new_group_video_call', 'group_id' => $data["group_id"]]);
                    }
                } else {
                    $existing_video_log = data_cache(['folder' => $call_log_folder, 'filename' => $data["group_id"], 'method' => 'get']);

                    $call_log = [
                        "_id" => $data["group_id"],
                        "online" => true,
                        'last_updated_on' => Registry::load('current_user')->time_stamp
                    ];

                    if (isset($existing_video_log['audio_only'])) {
                        $call_log['audio_only'] = true;
                    }

                    data_cache(['folder' => $call_log_folder, 'filename' => $data["group_id"], 'method' => 'add', 'data' => $call_log]);
                    ws_push(['update' => 'new_group_video_call', 'group_id' => $data["group_id"]]);
                }
            }

        } else if (isset($data['user_id'])) {
            $data["user_id"] = filter_var($data["user_id"], FILTER_SANITIZE_NUMBER_INT);

            if (!empty($data["user_id"])) {

                if (isset($data["audio_only_chat"])) {
                    $call_log_folder = 'private_audio_call_logs';
                } else {
                    $call_log_folder = 'private_video_call_logs';
                }

                $video_log_data = data_cache(['folder' => $call_log_folder, 'filename' => $data["user_id"], 'method' => 'get']);

                if (isset($data["offline"])) {
                    data_cache(['folder' => $call_log_folder, 'filename' => Registry::load('current_user')->id, 'method' => 'delete']);
                    ws_push(['update' => 'new_private_video_call', 'user_id' => $data["user_id"], 'caller_left' => true]);
                }

                if (isset($video_log_data['caller_id']) && (int)$video_log_data['caller_id'] === (int)Registry::load('current_user')->id) {

                    if (isset($data["offline"])) {
                        data_cache(['folder' => $call_log_folder, 'filename' => $data["user_id"], 'method' => 'delete']);
                    } else {
                        data_cache(['folder' => $call_log_folder, 'filename' => $data["user_id"], 'method' => 'append', 'data' => ['last_updated_on' => Registry::load('current_user')->time_stamp]]);
                        data_cache(['folder' => $call_log_folder, 'filename' => Registry::load('current_user')->id, 'method' => 'append', 'data' => ['last_updated_on' => Registry::load('current_user')->time_stamp]]);
                    }
                }
            }

        } else if (isset($data['call_log_delete'])) {

            if (isset($data["audio_only_chat"])) {
                $call_log_folder = 'private_audio_call_logs';
            } else {
                $call_log_folder = 'private_video_call_logs';
            }

            $calling_user_id = data_cache(['folder' => $call_log_folder, 'filename' => Registry::load('current_user')->id, 'method' => 'get']);
            data_cache(['folder' => $call_log_folder, 'filename' => Registry::load('current_user')->id, 'method' => 'delete']);

            if (!empty($calling_user_id) && isset($calling_user_id['caller_id'])) {
                $calling_user_id = $calling_user_id['caller_id'];
                ws_push(['update' => 'new_private_video_call', 'user_id' => $calling_user_id, 'call_rejected' => true]);
                ws_push(['update' => 'new_private_video_call', 'user_id' => Registry::load('current_user')->id]);
            }

        }
    }
}