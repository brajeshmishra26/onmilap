<?php

if (!Registry::load('current_user')->logged_in && isset(Registry::load('config')->load_private_conversation) && !empty(Registry::load('config')->load_private_conversation)) {
    $entry_page = Registry::load('config')->authentication_page_url_path.'/';

    if (!empty(Registry::load('config')->url_path)) {
        $entry_page .= '?redirect='.urlencode(Registry::load('config')->url_path);
    }

    redirect($entry_page);
} else if (!Registry::load('current_user')->logged_in && Registry::load('settings')->view_groups_without_login != 'enable') {

    $entry_page = Registry::load('config')->authentication_page_url_path.'/';

    if (!empty(Registry::load('config')->url_path)) {
        $entry_page .= '?redirect='.urlencode(Registry::load('config')->url_path);
    }

    redirect($entry_page);
} else if (Registry::load('current_user')->logged_in) {

    if (Registry::load('config')->csrf_token) {
        update_user_csrf_token();

        $browser_check = 'browser_check/';

        if (!empty(Registry::load('config')->url_path)) {
            $browser_check .= '?redirect='.urlencode(Registry::load('config')->url_path);
        }

        if (!isset($_GET['user_token']) || empty($_GET['user_token'])) {
            redirect($browser_check);
        } else {
            $current_user_csrf_token = Registry::load('current_user')->csrf_token;
            $entered_user_token = isset($_GET['user_token']) ? $_GET['user_token'] : '';

            if (strcasecmp($entered_user_token, $current_user_csrf_token) !== 0) {
                redirect($browser_check);
            }
        }
    }

    $pass_input_accept_attribute = true;

    if ($pass_input_accept_attribute) {
        include('layouts/chat_page/file_types_allowed.php');
    }
} else if (!Registry::load('current_user')->logged_in) {
    if (isset(Registry::load('config')->load_membership_packages)) {
        $entry_page = Registry::load('config')->authentication_page_url_path.'/';

        if (!empty(Registry::load('config')->url_path)) {
            $entry_page .= '?redirect='.urlencode(Registry::load('config')->url_path);
        }

        redirect($entry_page);
    }
}

if (Registry::load('settings')->chat_page_boxed_layout === 'enable') {
    Registry::load('appearance')->body_class = Registry::load('appearance')->body_class.' boxed_layout';
}
Registry::load('settings')->hide_groups_on_group_url = false;

if (isset(Registry::load('config')->load_group_conversation) && !empty(Registry::load('config')->load_group_conversation)) {

    if (role(['find' => 'hide_groups_on_group_url']) === 'yes') {
        Registry::load('settings')->hide_groups_on_group_url = true;
    }
}

if (isset(Registry::load('config')->load_group_conversation) && !empty(Registry::load('config')->load_group_conversation) || isset(Registry::load('config')->load_private_conversation) && !empty(Registry::load('config')->load_private_conversation)) {
    if (isset($_GET['display_chat_alone']) && $_GET['display_chat_alone']) {
        Registry::load('appearance')->display_chat_alone = true;
        Registry::load('settings')->load_group_info_on_group_load = 'no';
    }
}

if (!isset(Registry::load("config")->load_group_conversation) || empty(Registry::load("config")->load_group_conversation)) {
    $open_group_chat_on_load = role(['find' => 'open_group_chat_on_load']);

    if ($open_group_chat_on_load == 'yes') {
        $specify_group_chat_to_load = role(['find' => 'specify_group_chat_to_load']);
        $specify_group_chat_to_load = (int)$specify_group_chat_to_load;

        if (!empty($specify_group_chat_to_load)) {
            Registry::load("config")->load_group_conversation = $specify_group_chat_to_load;
        }
    }
}


?>