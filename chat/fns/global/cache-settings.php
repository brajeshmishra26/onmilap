<?php
$result = array();

$config_file_writeable = false;
$config_file = 'include/config.php';
$new_cfile_contents = null;

if (is_writable($config_file)) {
    $config_file_writeable = true;
    $new_cfile_contents = file_get_contents($config_file);
}

$columns = [
    'settings.setting', 'settings.value', 'settings.options'
];
$settings = DB::connect()->select('settings', $columns);
foreach ($settings as $setting) {
    $settingname = $setting['setting'];
    $setting_options = $setting['options'];

    if ($settingname === 'default_timezone' && empty($setting['value']) || $settingname === 'default_timezone' && $setting['value'] === 'Auto') {
        $result[$settingname] = "Australia/Sydney";
    } else {
        if (!empty($setting_options) && mb_strpos($setting_options, '[multi_select]') !== false || $settingname === 'disallowed_slugs') {
            if (!empty($setting['value'])) {
                $setting['value'] = @unserialize($setting['value']);
                if ($setting['value'] === false) {
                    $setting['value'] = array();
                } else {
                    $setting_value = array();
                    foreach ($setting['value'] as $value) {
                        $setting_value[$value] = $value;
                    }
                    $setting['value'] = $setting_value;
                }
            }
        }

        $result[$settingname] = $setting['value'];
    }

    if ($config_file_writeable) {
        if ($settingname === 'group_url_path') {
            $new_cfile_contents = preg_replace('/\$config->group_url_path\s*=\s*(.*?);/', '$config->group_url_path = \''.$setting['value'].'\';', $new_cfile_contents);
        } else if ($settingname === 'authentication_page_url_path') {
            $new_cfile_contents = preg_replace('/\$config->authentication_page_url_path\s*=\s*(.*?);/', '$config->authentication_page_url_path = \''.$setting['value'].'\';', $new_cfile_contents);
        } else if ($settingname === 'force_https') {
            $force_https = 'false';

            if ($setting['value'] === 'yes') {
                $force_https = 'true';
            }
            $new_cfile_contents = preg_replace('/\$config->force_https\s*=\s*(.*?);/', '$config->force_https = '.$force_https.';', $new_cfile_contents);
        } else if ($settingname === 'samesite_cookies') {

            $new_cfile_contents = preg_replace('/\$config->samesite_cookies\s*=\s*["\'].*?["\']\s*;/', '$config->samesite_cookies = \''.$setting['value'].'\';', $new_cfile_contents);
            $http_only_cookies = ($setting['value'] === 'strict') ? true : false;

            $new_cfile_contents = preg_replace(
                '/\$config->http_only_cookies\s*=\s*(true|false);\s*/',
                '$config->http_only_cookies = ' . ($http_only_cookies ? 'true' : 'false') . ";\n",
                $new_cfile_contents
            );

        } else if ($settingname === 'enable_redis') {

            $enable_redis = 'false';

            if ($setting['value'] === 'yes') {
                $enable_redis = 'true';
            }

            $new_cfile_contents = preg_replace('/\$config->enable_redis\s*=\s*(.*?);/', '$config->enable_redis = '.$enable_redis.';', $new_cfile_contents);
        } else if ($settingname === 'redis_host') {
            $new_cfile_contents = preg_replace('/\$config->redis_host\s*=\s*(.*?);/', '$config->redis_host = \''.$setting['value'].'\';', $new_cfile_contents);
        } else if ($settingname === 'redis_port') {
            $new_cfile_contents = preg_replace('/\$config->redis_port\s*=\s*(.*?);/', '$config->redis_port = \''.$setting['value'].'\';', $new_cfile_contents);
        } else if ($settingname === 'redis_password') {
            $new_cfile_contents = preg_replace('/\$config->redis_password\s*=\s*(.*?);/', '$config->redis_password = \''.$setting['value'].'\';', $new_cfile_contents);
        }
    }
}

if ($config_file_writeable && !empty($new_cfile_contents)) {
    file_put_contents($config_file, $new_cfile_contents);
}
$result['pause_userlog'] = random_string('10');
$result['cache_timestamp'] = strtotime("now");


$result['site_address'] = get_url();
$result['site_address']->url_path = '/';

$cache = json_encode($result);
$cachefile = 'assets/cache/settings.cache';

if (file_exists($cachefile)) {
    unlink($cachefile);
}

$cachefile = fopen($cachefile, "w");
fwrite($cachefile, $cache);
fclose($cachefile);

if (Registry::load('config')->enable_redis) {
    $redis_host = Registry::load('config')->redis_host;
    $redis_port = Registry::load('config')->redis_port;
    $redis_password = Registry::load('config')->redis_password;

    try {
        $redis = new Redis();
        $redis->connect($redis_host, $redis_port);

        if (!empty($redis_password)) {
            $redis->auth($redis_password);
        }

        $redis_key = md5('assets/cache/settings.cache');
        $redis->del($redis_key);

    } catch (Exception $e) {}
}

$robots_txt_rebuild = false;

if (isset($data['robots_txt_rebuild']) && $data['robots_txt_rebuild']) {
    $robots_txt_rebuild = true;
}

if ($robots_txt_rebuild || !file_exists('robots.txt')) {
    $site_url = rtrim(Registry::load('config')->site_url, '/');

    $robotsTxtContent = "User-agent: *\n";
    $robotsTxtContent .= "Disallow: /cgi-bin/\n";
    $robotsTxtContent .= "Disallow: /*?*login_session_id=\n";
    $robotsTxtContent .= "Disallow: /*?*session_time_stamp=\n";
    $robotsTxtContent .= "Disallow: /*?*access_code=\n\n";

    $robotsTxtContent .= "User-agent: Mediapartners-Google\n";
    $robotsTxtContent .= "Allow: /\n\n";

    $robotsTxtContent .= "Sitemap: " . $site_url . "/sitemap/";

    file_put_contents('robots.txt', $robotsTxtContent);
}

$result = true;