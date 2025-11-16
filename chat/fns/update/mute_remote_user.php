<?php
if (Registry::load('current_user')->logged_in) {
    if (Registry::load('settings')->video_chat !== 'disable') {
        if (isset($data['group_id'], $data['user_id'])) {

            $data['group_id'] = filter_var($data["group_id"], FILTER_SANITIZE_NUMBER_INT);
            $data['user_id'] = filter_var($data["user_id"], FILTER_SANITIZE_NUMBER_INT);

            if (!empty($data['group_id']) && !empty($data['user_id'])) {

                $super_privileges = false;

                if (role(['permissions' => ['groups' => 'super_privileges']])) {
                    $super_privileges = true;
                }

                $columns = $join = $where = null;
                $columns = [
                    'groups.name(group_name)', 'group_roles.group_role_attribute', 'groups.suspended',
                    'groups.slug', 'groups.secret_group', 'groups.password', 'groups.suspended', 'groups.updated_on',
                    'group_members.group_role_id', 'group_members.banned_till', 'groups.who_all_can_send_messages', 'groups.enable_video_chat',
                    'groups.enable_audio_chat'
                ];

                $join["[>]group_members"] = ["groups.group_id" => "group_id", "AND" => ["user_id" => Registry::load('current_user')->id]];
                $join["[>]group_roles"] = ["group_members.group_role_id" => "group_role_id"];
                $where["groups.group_id"] = $data["group_id"];
                $where["LIMIT"] = 1;
                $group_info = DB::connect()->select('groups', $join, $columns, $where);

                if (isset($group_info[0])) {
                    $group_info = $group_info[0];
                } else {
                    return;
                }
                if ($super_privileges || role(['permissions' => ['group' => 'mute_users_during_call'], 'group_role_id' => $group_info['group_role_id']])) {

                    $realtime_log_data = array();
                    $realtime_log_data["log_type"] = 'mute_remote_user';

                    if (isset($data['unmute'])) {
                        $realtime_log_data["log_type"] = 'unmute_remote_user';
                    }

                    $realtime_log_data["related_parameters"] = [
                        "group_id" => $data['group_id'],
                        "user_id" => $data['user_id'],
                    ];
                    $realtime_log_data["related_parameters"] = json_encode($realtime_log_data["related_parameters"]);
                    $realtime_log_data["created_on"] = Registry::load('current_user')->time_stamp;
                    DB::connect()->insert("realtime_logs", $realtime_log_data);

                    ws_push(['update' => 'new_realtime_log']);
                }
            }
        }
    }
}
?>