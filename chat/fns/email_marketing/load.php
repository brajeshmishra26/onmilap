<?php
function email_marketing_module($data) {
    $result = array();
    $result['success'] = false;
    $result['error_message'] = Registry::load('strings')->something_went_wrong;

    $data['platform'] = null;

    if (Registry::load('settings')->email_marketing_platform !== 'disable') {
        $data['platform'] = Registry::load('settings')->email_marketing_platform;
    }

    if (!empty($data['platform'])) {

        $find_platform = $data['platform'];

        if (!empty($find_platform)) {
            $find_platform = preg_replace("/[^a-zA-Z0-9_]+/", "", $find_platform);
            $find_platform = str_replace('libraries', '', $find_platform);
        }

        if (!empty($find_platform)) {
            $function_file = 'fns/email_marketing/'.$find_platform.'.php';
            if (file_exists($function_file)) {
                include($function_file);
            }
        }
    }

    return $result;
}