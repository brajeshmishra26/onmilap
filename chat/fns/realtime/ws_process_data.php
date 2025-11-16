<?php

$ws_solo_requests = ['site_notification', 'new_private_chat_message', 'last_seen_by_recipient', 'friends_list', 'new_private_video_call', 'reload_page'];
$ws_update_request = $ws_data['update'];
$skip_ws_solo_requests = false;
$columns = $join = $where = null;

if ($ws_data['update'] === "new_private_chat_message" || $ws_data['update'] === "last_seen_by_recipient") {
    if (!empty($monitoring_private_chat)) {
        $skip_ws_solo_requests = true;
    }
}

if (!Registry::stored('site_role_attributes')) {
    $site_role_attributes_data = extract_json(['file' => 'assets/cache/site_role_attributes.cache']);
    Registry::add('site_role_attributes', $site_role_attributes_data);
}

if (!Registry::stored('group_role_attributes')) {
    $group_role_attributes_data = extract_json(['file' => 'assets/cache/group_role_attributes.cache']);
    Registry::add('group_role_attributes', $group_role_attributes_data);
}

if (!$skip_ws_solo_requests && in_array($ws_update_request, $ws_solo_requests)) {

    $clientFd = null;
    $clientFds = array();
    $data = array();
    $check_user_id = null;
    $active_user = null;
    $result = array();

    if ($ws_data['update'] === "site_notification" && isset($ws_data['user_id'])) {
        $check_user_id = $ws_data['user_id'];
    } else if ($ws_data['update'] === "new_private_chat_message" || $ws_data['update'] === "last_seen_by_recipient") {
        if (isset($ws_data['receiver_id'])) {
            $check_user_id = $ws_data['receiver_id'];
        }
    } else if ($ws_data['update'] === "friends_list" && isset($ws_data['user_id'])) {
        $check_user_id = $ws_data['user_id'];
    } else if ($ws_data['update'] === "new_private_video_call" && isset($ws_data['user_id'])) {
        $check_user_id = $ws_data['user_id'];
    } else if ($ws_data['update'] === "reload_page" && isset($ws_data['user_id'])) {
        $check_user_id = $ws_data['user_id'];
    }

    if (!empty($check_user_id)) {

        if ($ws_users instanceof Swoole\Table && $ws_users->exists($check_user_id)) {

            $user_data = $ws_users->get($check_user_id);
            if (!empty($user_data['fds'])) {
                $decoded_fds = json_decode($user_data['fds'], true);
                if (is_array($decoded_fds)) {
                    $clientFds = $decoded_fds;
                }
            }

            if (!empty($clientFds)) {

                $foundFd = null;

                if (is_array($clientFds) && $ws_clients instanceof Swoole\Table) {
                    foreach ($clientFds as $check_fd) {
                        if ($ws_clients->exists($check_fd)) {
                            $foundFd = $check_fd;
                            $active_user = $ws_clients->get($foundFd);
                            $current_user_info = json_decode($active_user["info"]);

                            $current_user_info->language = $current_user_info->language ?? 1;

                            Registry::add('current_user', $current_user_info);

                            Registry::add('strings', getLanguageStrings($current_user_info->language));
                            Registry::add('permissions', getRolePermissions($current_user_info->site_role));
                            break;
                        }
                    }
                }

                if ($foundFd !== null) {
                    foreach ($clientFds as $fd_in_loop) {

                        if ($ws_clients instanceof Swoole\Table && $ws_clients->exists($fd_in_loop)) {

                            $current_user_id = $check_user_id;
                            $result = [];
                            $data = [];

                            $active_user = $ws_clients->get($fd_in_loop);

                            if (isset($active_user['chat_context'])) {
                                $data = json_decode($active_user['chat_context'], true);
                            }

                            if (!empty($active_user)) {
                                if ($ws_data['update'] === "site_notification") {
                                    if (isset($data["unread_site_notifications"])) {
                                        include('fns/realtime/unread_site_notifications.php');
                                    }
                                } else if ($ws_data['update'] === "friends_list") {
                                    if (isset($data["pending_friend_requests"])) {
                                        if (Registry::load('settings')->friend_system === 'enable') {
                                            include('fns/realtime/pending_friend_requests.php');
                                        }
                                    }
                                } else if ($ws_data['update'] === "reload_page") {
                                    $result['reload_page'] = true;
                                } else if ($ws_data['update'] === "new_private_video_call") {

                                    if (isset($data['user_id'])) {
                                        $data["user_id"] = filter_var($data["user_id"], FILTER_SANITIZE_NUMBER_INT);
                                    }
                                    if (isset($data['user_id']) && !empty($data['user_id']) && isset($ws_data['call_rejected'])) {
                                        $result['video_chat_status'] = array();
                                        $result['video_chat_status']['user_id'] = $data['user_id'];
                                        $result['video_chat_status']['rejected'] = true;
                                    } else if (isset($ws_data['caller_left'])) {
                                        $result['new_call_notification'] = array();
                                    } else if (isset($data["check_call_logs"])) {
                                        if (isset($data["current_call_id"])) {
                                            include('fns/realtime/check_call_logs.php');
                                        }
                                    }

                                } else if ($ws_data['update'] === "new_private_chat_message" || $ws_data['update'] === "last_seen_by_recipient") {

                                    if (isset($ws_data['sender_id']) && isset($data["user_id"])) {
                                        if ((int)$ws_data['sender_id'] === (int)$data["user_id"]) {

                                            if ($ws_data['update'] === "new_private_chat_message") {
                                                include('fns/realtime/private_chat_messages.php');
                                            }

                                            if (isset($data["last_seen_by_recipient"])) {
                                                if (role(['permissions' => ['private_conversations' => 'check_read_receipts']])) {
                                                    include('fns/realtime/last_seen_by_recipient.php');
                                                }
                                            }
                                        }
                                    }

                                    if (isset($data["unread_private_chat_messages"])) {
                                        include('fns/realtime/unread_private_chat_messages.php');
                                    }

                                }

                                if (!empty($result)) {
                                    if ($ws_instance->isEstablished($fd_in_loop)) {
                                        $ws_instance->push($fd_in_loop, json_encode($result));
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
    }


} else {
    $columns = $join = $where = null;
    $clientFd = null;

    foreach ($ws_clients as $fd => $active_users) {

        $result = array();
        $clientFd = $active_users['fd'];
        $break_foreach = false;
        $get_client_user_data = true;

        if ($ws_data['update'] === "online_users") {
            $get_client_user_data = false;
        }

        if ($get_client_user_data) {

            $current_user_info = json_decode($active_users["info"]);

            Registry::add('current_user', $current_user_info);

            if (!isset($current_user_info->language)) {
                $current_user_info->language = 1;
            }

            Registry::add('strings', getLanguageStrings($current_user_info->language));
            Registry::add('permissions', getRolePermissions($current_user_info->site_role));
        }

        $data = array();

        if (isset($active_users['chat_context'])) {
            $data = json_decode($active_users['chat_context'], true);
        }

        $current_user_id = $active_users['user_id'];

        if ($get_client_user_data && $ws_data['update'] === "user_fp_token") {
            if (Registry::load('current_user')->logged_in) {
                if (isset($data["userFpToken"])) {

                    $client_data = $ws_clients->get($clientFd);

                    if ($client_data !== false) {
                        $client_data['userFpToken'] = $data["userFpToken"];
                        $ws_clients->set($clientFd, $client_data);
                        if (isset(Registry::load('settings')->fingerprint_module) && Registry::load('settings')->fingerprint_module !== 'disable') {
                            include('fns/realtime/userFpToken.php');
                        }
                    }
                }
            }
        } else if ($ws_data['update'] === "new_group_video_call") {
            if (isset($data["video_chat_status"]) && isset($data["group_id"])) {
                include('fns/realtime/video_chat_status.php');
            }
        } else if ($ws_data['update'] === "new_online_group_member") {
            if (isset($data["online_group_members"]) && isset($data["group_id"])) {
                include('fns/realtime/online_group_members.php');
            }
        } else if ($ws_data['update'] === "user_typing") {

            if (isset($data["whos_typing_last_logged_user_id"])) {
                if (isset($ws_data['group_id']) && isset($data["group_id"]) && role(['permissions' => ['groups' => 'typing_indicator']])) {
                    if ((int)$ws_data['group_id'] === (int)$data["group_id"]) {
                        include('fns/realtime/whos_typing.php');
                    }
                } else if (isset($ws_data['user_id']) && isset($data["user_id"]) && role(['permissions' => ['private_conversations' => 'typing_indicator']])) {
                    if ((int)$ws_data['user_id'] === (int)$data["user_id"]) {
                        include('fns/realtime/whos_typing.php');
                    }
                }
            }
        } else if ($ws_data['update'] === "new_realtime_log") {
            if (isset($data["last_realtime_log_id"])) {
                include('fns/realtime/realtime_logs.php');
            }

        } if ($ws_data['update'] === "new_private_chat_message" || $ws_data['update'] === "last_seen_by_recipient") {

            if (isset($ws_data['receiver_id'])) {
                if ((int)$ws_data['receiver_id'] === (int)$current_user_id || isset($data["user_id"]) && $data["user_id"] === 'all') {
                    if (isset($ws_data['sender_id']) && isset($data["user_id"])) {
                        if ((int)$ws_data['sender_id'] === (int)$data["user_id"] || $data["user_id"] === 'all') {

                            if ($ws_data['update'] === "new_private_chat_message") {
                                include('fns/realtime/private_chat_messages.php');
                            }

                            if ($data["user_id"] !== 'all' && isset($data["last_seen_by_recipient"])) {
                                if (role(['permissions' => ['private_conversations' => 'check_read_receipts']])) {
                                    include('fns/realtime/last_seen_by_recipient.php');
                                }
                            }
                        }
                    }

                    if (isset($data["unread_private_chat_messages"])) {
                        include('fns/realtime/unread_private_chat_messages.php');
                    }
                }
            }

        } else if ($ws_data['update'] === "complaints") {
            if (isset($data["unresolved_complaints"])) {
                include('fns/realtime/unresolved_complaints.php');
            }
        } else if ($ws_data['update'] === "online_users") {
            if (isset($data["recent_online_user_id"])) {
                include('fns/realtime/online_users.php');
            }
        } else if ($ws_data['update'] === "new_group_message") {
            if (isset($ws_data['group_id']) && isset($data["group_id"])) {
                if ((int)$ws_data['group_id'] === (int)$data["group_id"] || $data["group_id"] === 'all') {
                    include('fns/realtime/group_messages.php');
                }
            }
            if (isset($data["unread_group_messages"])) {
                include('fns/realtime/unread_group_messages.php');
            }
        }

        if (isset($result) && !empty($result)) {
            if ($ws_instance->isEstablished($clientFd)) {
                $ws_instance->push($clientFd, json_encode($result));
            }
        }

        if ($break_foreach) {
            break;
        }
    }
}

if (isset(DB::connect()->pdo)) {
    DB::connect()->pdo = null;
}

DB::closeConnection();
?>