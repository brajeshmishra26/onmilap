<?php

function ip_intelligence_blacklist($ip) {

    $blacklist = DB::connect()->count('ip_intel_blacklist', ['ip_address' => $ip]);

    if ($blacklist > 0) {
        return true;
    } else {
        return false;
    }
}

function ip_intelligence($data = null) {
    $result = array();
    $result['success'] = true;
    $ip_address = null;
    $blacklisted_on_db = false;

    if (!empty($data) && is_array($data)) {
        if (isset($data['ip_address'])) {
            $ip_address = $data['ip_address'];
        }
    }

    if (empty($ip_address)) {
        $ip_address = Registry::load('current_user')->ip_address;
    }

    if ($ip_address !== '127.0.0.1') {

        if (!empty(Registry::load('settings')->ip_intelligence) && Registry::load('settings')->ip_intelligence !== 'disable') {

            if (!filter_var($ip_address, FILTER_VALIDATE_IP)) {
                $result['success'] = false;
                $result['error_message'] = 'Invalid IP address';
                return $result;
            }


            if (ip_intelligence_blacklist($ip_address)) {
                $result['success'] = false;
                $blacklisted_on_db = true;
            } else {
                $ip_intel_service = Registry::load('settings')->ip_intelligence;

                if (isset($ip_intel_service) && !empty($ip_intel_service)) {
                    $load_fn_file = 'fns/ip_intelligence/'.$ip_intel_service.'.php';
                    if (file_exists($load_fn_file)) {
                        include($load_fn_file);
                    }
                }
            }
        }
    }

    if (!$result['success'] && !$blacklisted_on_db) {
        DB::connect()->insert('ip_intel_blacklist', ['ip_address' => $ip_address]);
    }
    return $result;
}