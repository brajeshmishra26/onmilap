<?php

function web_shield($todo, $private_data = null) {

    if ($todo === 'is_blacklisted') {

        $result = false;

        if (!empty($private_data) && $private_data !== '127.0.0.1') {
            $is_blacklisted = DB::connect()->count('blacklisted_ips', ['ip_address' => $private_data]);

            if ((int)$is_blacklisted > 0) {
                $result = true;
            }
        }
        return $result;
    } else if ($todo === 'get_user_ip') {
        if (isset($_SERVER["HTTP_CF_CONNECTING_IP"])) {
            $_SERVER['REMOTE_ADDR'] = $_SERVER["HTTP_CF_CONNECTING_IP"];
            $_SERVER['HTTP_CLIENT_IP'] = $_SERVER["HTTP_CF_CONNECTING_IP"];
        }
        $client = @$_SERVER['HTTP_CLIENT_IP'];
        $forward = @$_SERVER['HTTP_X_FORWARDED_FOR'];
        $remote = $_SERVER['REMOTE_ADDR'];
        if (filter_var($client, FILTER_VALIDATE_IP)) {
            $ip = $client;
        } elseif (filter_var($forward, FILTER_VALIDATE_IP)) {
            $ip = $forward;
        } else {
            $ip = $remote;
        }
        if ($ip == '') {
            $ip = '127.0.0.1';
        } else if ($ip == '::1') {
            $ip = '127.0.0.1';
        }
        return $ip;
    } else if ($todo === 'get_user_agent') {

        if (empty($agent) && isset($_SERVER['HTTP_USER_AGENT'])) {
            $agent = $_SERVER['HTTP_USER_AGENT'];
        }

        $result = [
            'browser' => 'Unknown Browser',
            'version' => '?',
            'platform' => 'Unknown Platform',
            'user_agent' => $agent ?? 'No User Agent Available'
        ];

        if (empty($agent)) {
            return $result;
        }

        // Browser list
        $browser_array = [
            '/msie/i' => 'Internet Explorer',
            '/mobile/i' => 'Handheld Browser',
            '/firefox/i' => 'Firefox',
            '/safari/i' => 'Safari',
            '/chrome/i' => 'Chrome',
            '/opera/i' => 'Opera',
            '/edge/i' => 'Edge',
            '/edg/i' => 'Edge',
            '/opr/i' => 'Opera',
            '/netscape/i' => 'Netscape',
            '/maxthon/i' => 'Maxthon',
            '/konqueror/i' => 'Konqueror'
        ];

        $regexfound = '';

        foreach ($browser_array as $regex => $value) {
            if (preg_match($regex, $agent)) {
                $result['browser'] = $value;
                $regexfound = trim($regex, '/i');
                break;
            }
        }

        // Platform list
        $platform_array = [
            '/windows nt 10/i' => 'Windows 10',
            '/windows nt 6.3/i' => 'Windows 8.1',
            '/windows nt 6.2/i' => 'Windows 8',
            '/windows nt 6.1/i' => 'Windows 7',
            '/windows nt 6.0/i' => 'Windows Vista',
            '/windows nt 5.2/i' => 'Windows Server 2003/XP x64',
            '/windows nt 5.1/i' => 'Windows XP',
            '/windows xp/i' => 'Windows XP',
            '/windows nt 5.0/i' => 'Windows 2000',
            '/windows me/i' => 'Windows ME',
            '/win98/i' => 'Windows 98',
            '/win95/i' => 'Windows 95',
            '/win16/i' => 'Windows 3.11',
            '/macintosh|mac os x/i' => 'Mac OS X',
            '/mac_powerpc/i' => 'Mac OS 9',
            '/linux/i' => 'Linux',
            '/ubuntu/i' => 'Ubuntu',
            '/iphone/i' => 'iPhone',
            '/ipod/i' => 'iPod',
            '/ipad/i' => 'iPad',
            '/android/i' => 'Android',
            '/blackberry/i' => 'BlackBerry',
            '/webos/i' => 'Mobile'
        ];

        foreach ($platform_array as $regex => $value) {
            if (preg_match($regex, $agent)) {
                $result['platform'] = $value;
                break;
            }
        }
        $known = ['Version', $regexfound, 'other'];
        $pattern = '#(?<browser>' . join('|', $known) . ')[/ ]+(?<version>[0-9.|a-zA-Z]*)#';

        if (preg_match_all($pattern, $agent, $matches)) {
            $count = count($matches['browser']);
            if ($count > 1) {
                if (strripos($agent, "Version") < strripos($agent, $result['browser'])) {
                    $result['version'] = $matches['version'][0] ?? '?';
                } else {
                    $result['version'] = $matches['version'][1] ?? '?';
                }
            } else {
                $result['version'] = $matches['version'][0] ?? '?';
            }
        }

        return $result;
    }
}

?>