<?php

function realtime($data, $private_data = null) {

    $result = array();
    $timeout = $api_request = false;
    $start_time = time();

    $current_user_id = Registry::load('current_user')->id;

    if (Registry::load('current_user')->logged_in) {
        if (isset($data["sys_tasks"])) {
            include('fns/realtime/sys_tasks.php');
        } else if (isset($data["abc_trigger"])) {
            include('fns/realtime/abc_trigger.php');
        }
    }

    if (Registry::load('current_user')->logged_in) {
        if (isset($data["userFpToken"])) {
            if (isset(Registry::load('settings')->fingerprint_module) && Registry::load('settings')->fingerprint_module !== 'disable') {
                include('fns/realtime/userFpToken.php');
            }
        }
    }

    if (isset($data["api_secret_key"]) && !empty($data["api_secret_key"])) {
        if (isset(Registry::load('settings')->api_secret_key) && !empty(Registry::load('settings')->api_secret_key)) {
            if ($data["api_secret_key"] === Registry::load('settings')->api_secret_key) {
                $api_request = true;
            }
        }
    }

    if ($api_request) {
        if (isset($data['user'])) {
            $columns = $join = $where = null;

            $columns = ['site_users.user_id'];
            $where["OR"] = ["site_users.username" => $data['user'], "site_users.email_address" => $data['user']];
            $where["LIMIT"] = 1;

            $site_user = DB::connect()->select('site_users', $columns, $where);

            if (isset($site_user[0])) {
                $current_user_id = $site_user[0]['user_id'];
            } else {
                $user_id = 0;
            }
        }
    }


    $columns = $join = $where = null;

    $long_polling_time_out = Registry::load('settings')->request_timeout;

    if (empty($long_polling_time_out)) {
        $long_polling_time_out = 10;
    }
    $set_time_limit = $long_polling_time_out+5;
    $escape = false;

    session_write_close();
    ignore_user_abort(false);
    set_time_limit($set_time_limit);


    if (Registry::load('current_user')->logged_in) {
        if (Registry::load('settings')->realtime_mode !== 'websocket') {
            if (!isset(Registry::load('current_user')->online_status) || (int)Registry::load('current_user')->online_status !== 1) {
                $update_status = [
                    'online_status' => 1,
                    "last_seen_on" => Registry::load('current_user')->time_stamp,
                    "updated_on" => Registry::load('current_user')->time_stamp,
                ];
                DB::connect()->update('site_users', $update_status, ['user_id' => $current_user_id]);
            }
        }
    }

    while (!$timeout) {
        $timeout = (time() - $start_time) > $long_polling_time_out;

        if ($timeout) {
            break;
        } else {

            if (isset($data["group_id"])) {
                include('fns/realtime/group_messages.php');
            }

            if ($api_request || Registry::load('current_user')->logged_in) {

                if (isset($data["video_chat_status"])) {
                    if (isset($data["group_id"]) || isset($data["user_id"])) {
                        include('fns/realtime/video_chat_status.php');
                    }
                }

                if (isset($data["check_call_logs"])) {
                    if (isset($data["current_call_id"])) {
                        include('fns/realtime/check_call_logs.php');
                    }
                }


                if (isset($data["user_id"])) {
                    include('fns/realtime/private_chat_messages.php');

                    if ($data["user_id"] !== 'all' && isset($data["last_seen_by_recipient"])) {
                        if (role(['permissions' => ['private_conversations' => 'check_read_receipts']])) {
                            include('fns/realtime/last_seen_by_recipient.php');
                        }
                    }
                }

                if ($api_request) {
                    $rt_inputs = ['unread_group_messages', 'unread_private_chat_messages', 'unread_site_notifications'];

                    foreach ($rt_inputs as $rt_input) {
                        if (!isset($data[$rt_input])) {
                            $data[$rt_input] = 0;
                        }
                    }
                }

                if ($api_request || Registry::load('settings')->disable_groups !== 'yes') {
                    if ($api_request || isset($data["unread_group_messages"])) {
                        include('fns/realtime/unread_group_messages.php');
                    }
                }

                if ($api_request || isset($data["unread_private_chat_messages"])) {
                    include('fns/realtime/unread_private_chat_messages.php');
                }

                if ($api_request || isset($data["unread_site_notifications"])) {
                    include('fns/realtime/unread_site_notifications.php');
                }

                if (isset($data["whos_typing_last_logged_user_id"])) {
                    if (isset($data["group_id"]) && role(['permissions' => ['groups' => 'typing_indicator']])) {
                        include('fns/realtime/whos_typing.php');
                    } else if (isset($data["user_id"]) && role(['permissions' => ['private_conversations' => 'typing_indicator']])) {
                        include('fns/realtime/whos_typing.php');
                    }
                }

                if (isset($data["unresolved_complaints"])) {
                    include('fns/realtime/unresolved_complaints.php');
                }

                if (isset($data["pending_friend_requests"])) {
                    if (Registry::load('settings')->friend_system === 'enable') {
                        include('fns/realtime/pending_friend_requests.php');
                    }
                }

            } else {
                if (isset($data["logged_in_user_id"]) && !empty($data["logged_in_user_id"])) {
                    if ((int)$data["logged_in_user_id"] !== (int)$current_user_id) {
                        $result['reload_page'] = true;
                        $escape = true;
                    }
                }
            }

            if (isset($data["recent_online_user_id"])) {
                include('fns/realtime/online_users.php');
            }

            if (isset($data["last_realtime_log_id"])) {
                include('fns/realtime/realtime_logs.php');
            }

            if (isset($data["online_group_members"])) {
                include('fns/realtime/online_group_members.php');
            }

            if ($escape || $api_request) {
                break;
            }

        }

        sleep(1);
    }

    if ($timeout || $escape) {

        if ($api_request) {
            if (isset($result['play_sound_notification'])) {
                unset($result['play_sound_notification']);
            }
        }

        if (isset($data["return"]) && $data["return"]) {
            return $result;
        } else {
            $result = json_encode($result);
            echo $result;
        }

        if (function_exists('fastcgi_finish_request')) {
            fastcgi_finish_request();
        }

        die();
    }
}

?>