<?php

session_write_close();
ignore_user_abort(false);

include 'fns/firewall/load.php';
include_once 'fns/sql/load.php';
include 'fns/variables/load.php';

$api_request_page = true;
include 'fns/global/um_mode.php';

$skip_api_request = false;
$api_request_session = false;

if (Registry::load('current_user')->logged_in) {
    if (isset($_REQUEST['update']) && $_REQUEST['update'] === 'settings') {
        if (isset($_REQUEST["api_secret_key"])) {
            $skip_api_request = true;
        }
    }
}

if (isset($_REQUEST["api_secret_key"]) && !empty($_REQUEST["api_secret_key"]) && !$skip_api_request) {
    if (isset(Registry::load('settings')->api_secret_key) && !empty(Registry::load('settings')->api_secret_key)) {
        if ($_REQUEST["api_secret_key"] === Registry::load('settings')->api_secret_key) {
            $data = get_data('request');
            $api_request_session = true;
        } else {
            $result = array();
            $result['success'] = false;
            $result['error_message'] = 'Invalid API Secret Key';
            $result['error_key'] = 'invalid_api_secret_key';
            $result = json_encode($result);
            echo $result;
            return;
        }
    } else {
        return false;
    }
} else {
    $data = get_data();
}

if (Registry::load('settings')->minify_html_output === 'yes') {
    $ob_enabled = ini_get('output_buffering');

    if ($ob_enabled) {
        ob_start('minify_output');
    } else {
        Registry::load('settings')->minify_html_output = 'no';
    }
}

if (Registry::load('current_user')->logged_in) {
    if (Registry::load('config')->csrf_token && !$api_request_session) {
        if (!empty(Registry::load('current_user')->csrf_token)) {

            $itoken_scount = (isset($_COOKIE["itoken_scount"]) && !empty($_COOKIE["itoken_scount"]) && is_numeric($_COOKIE["itoken_scount"])) ? (int)$_COOKIE["itoken_scount"] : 0;

            if (!isset($data["csrf_token"]) || $data["csrf_token"] !== Registry::load('current_user')->csrf_token) {

                $result = array();
                $result['success'] = false;
                $result['error_message'] = 'Invalid user token. Please try again.';
                $result['error_key'] = 'invalid_csrf_token';
                $result['alert'] = ['message' => 'Invalid user token. Please try again.'];

                if (isset($data["realtime"])) {
                    if (Registry::load('current_user')->site_role_attribute !== 'administrators') {
                        if (isset(Registry::load('settings')->fingerprint_module) && Registry::load('settings')->fingerprint_module !== 'disable') {
                            $user_fp = DB::connect()->count('site_users_fingerprints', ["user_id" => Registry::load('current_user')->id, "fp_disabled" => 0]);
                            if (empty($user_fp)) {
                                Registry::load('current_user')->csrf_token = 'invalid_csrf';
                            }
                        }
                    }

                    $result['tk_user_code'] = Registry::load('current_user')->csrf_token;
                    $result['reload_page'] = true;
                }

                $itoken_scount = (int)$itoken_scount+1;
                add_cookie('itoken_scount', $itoken_scount);

                if ((int)$itoken_scount >= 6) {
                    include_once('fns/update/load.php');
                    update(['update' => 'firewall', 'ban_user_id' => Registry::load('current_user')->id, 'return' => true], ["force_request" => true]);
                    update(['update' => 'site_user_role', 'ban_user_id' => Registry::load('current_user')->id, 'return' => true], ["force_request" => true]);
                }

                $result = json_encode($result);
                echo $result;
                return;
            } else {

                if (!empty($itoken_scount)) {
                    add_cookie('itoken_scount', 0);
                }

                if (!empty(Registry::load('current_user')->csrf_token_generated_on)) {
                    $csrf_d1 = new DateTime(Registry::load('current_user')->time_stamp);
                    $csrf_d2 = new DateTime(Registry::load('current_user')->csrf_token_generated_on);
                    $csrf_interval = $csrf_d1->diff($csrf_d2);
                    $csrf_total_minutes = ($csrf_interval->days * 24 * 60) + ($csrf_interval->h * 60) + $csrf_interval->i;
                    if ((int)$csrf_total_minutes > 2) {
                        update_user_csrf_token(['force_request' => true]);
                    }
                }


            }
        }
    }
} else {
    if (isset($data["landing_page_groups"])) {
        include('layouts/landing_page/groups.php');
    }
}

if (isset($data["load"])) {
    include 'fns/load/load.php';
    load($data);
} else if (isset($data["form"])) {
    include 'fns/form/load.php';
    form($data);
} else if (isset($data["add"])) {
    include 'fns/add/load.php';
    add($data);
} else if (isset($data["update"])) {
    include 'fns/update/load.php';
    update($data);
} else if (isset($data["download"])) {
    include 'fns/download/load.php';
    download($data);
} else if (isset($data["upload"])) {
    include 'fns/upload/load.php';
    upload($data);
} else if (isset($data["remove"])) {
    include 'fns/remove/load.php';
    remove($data);
} else if (isset($data["popup"])) {
    include 'fns/popup/load.php';
    popup($data);
} else if (isset($data["realtime"])) {
    include 'fns/realtime/load.php';
    realtime($data);
} else if (isset($data["fetch_info"]) && Registry::load('current_user')->logged_in) {
    include 'fns/fetch/load.php';
    fetch($data);
}

if (Registry::load('settings')->minify_html_output === 'yes') {
    ob_end_flush();
}

if (isset(DB::connect()->pdo)) {
    DB::connect()->pdo = null;
}

DB::closeConnection();
?>