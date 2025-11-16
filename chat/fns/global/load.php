<?php

function random_string($data = null) {
    $length = rand(8, 20);
    $result = null;
    $character_set = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";

    if (isset($data['length']) && !empty($data['length'])) {
        $length = $data['length'];
    }

    if (isset($data['character_set']) && !empty($data['character_set'])) {
        $character_set = $data['character_set'];
    }

    $result = substr(str_shuffle($character_set), 1, $length);
    return $result;
}

function isRedisValid($host = '127.0.0.1', $port = 6379, $password = null, $timeout = 2): bool {
    $redis = new Redis();

    try {
        $redis->connect($host, $port, $timeout);

        if (!empty($password)) {
            if (!$redis->auth($password)) {
                return false;
            }
        }
        
        $ping = $redis->ping();

        return $ping === '+PONG' || $ping === true || $ping === 1;
    } catch (RedisException $e) {
        return false;
    }
}

function minify_output($buffer) {
    $buffer = preg_replace('/<!--(?!\[if).*?-->/', '', $buffer);

    $buffer = preg_replace('/>\s+</', '><', $buffer);

    $buffer = preg_replace('/\s{2,}/', ' ', $buffer);

    return trim($buffer);
}



function extract_json($data) {
    $result = array();

    $useRedis = false;
    $store_redis = false;

    if (isset($data['use_redis']) && $data['use_redis'] && Registry::load('config')->enable_redis) {
        $useRedis = true;
    }

    if ($useRedis) {
        $redis_host = Registry::load('config')->redis_host;
        $redis_port = Registry::load('config')->redis_port;
        $redis_password = Registry::load('config')->redis_password;

        $redis = new Redis();
        try {
            $redis->connect($redis_host, $redis_port);
            if (!empty($redis_password)) {
                $redis->auth($redis_password);
            }

            if (isset($data['file']) && !empty($data['file'])) {
                $redis_key = md5($data['file']);
                $cached = $redis->get($redis_key);
                if ($cached) {
                    $result = json_decode($cached);
                } else {
                    $store_redis = true;
                }
            } else {
                $useRedis = false;
            }
        } catch (Exception $e) {
            $useRedis = false;
            $store_redis = false;
        }
    }

    if (empty($result)) {
        if (isset($data['file']) && !empty($data['file'])) {
            $file_content = file_get_contents($data['file']);
            if ($file_content !== false) {
                $result = json_decode($file_content);


                if ($store_redis && isset($redis) && $redis instanceof Redis) {
                    try {
                        $redis_key = md5($data['file']);
                        $redis->set($redis_key, $file_content);
                    } catch (Exception $e) {}
                }
            }
        } elseif (isset($data['json']) && !empty($data['json'])) {
            $result = json_decode($data['json']);
        }
    }

    if (!empty($result)) {
        if (isset($data['extract']) && !empty($data['extract']) && isset($data['subkey']) && !empty($data['subkey'])) {
            $result = $result->$data['extract'];

            foreach ($result as $key => $val) {
                if ($key == $data['subkey']) {
                    foreach ($val as $subkey => $value) {
                        $result[$key][$subkey] = $value;
                    }
                } else {
                    foreach ($val as $subkey => $value) {
                        $result[$key][$subkey] = true;
                    }
                }
            }
        } elseif (isset($data['extract']) && !empty($data['extract'])) {
            $result = get_object_vars($result);
            $extract = $data['extract'];

            if (isset($result[$extract])) {
                $result = json_decode($result[$extract]);
                $result = get_object_vars($result);
            }
        }
    }

    return $result;
}



function rangeof_chars($string, $encoding = 'utf8') {
    $result = '';
    for ($i = 0, $len = mb_strlen($string); $i < $len; ++$i) {
        $l = mb_strtolower(mb_substr($string, $i, 1, $encoding));
        $u = mb_strtoupper(mb_substr($string, $i, 1, $encoding));
        if ($l != $u) {
            $result .= "[{$l}{$u}]";
        } else {
            $result .= mb_substr($string, $i, 1, $encoding);
        }
    }
    return $result;
}

function output($string, $todo = null) {
    if (empty($todo)) {
        $result = htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
    } elseif ($todo === 'skip') {
        $result = $string;
    } elseif ($todo === 'number') {
        $result = filter_var($string, FILTER_SANITIZE_NUMBER_INT);
    }
    echo $result;
}

function add_cookie($name, $value, $time = 0, $path = "/") {

    if (isset(Registry::load('config')->samesite_cookies) && Registry::load('config')->samesite_cookies !== 'default') {
        $samesite_cookies = ucfirst(Registry::load('config')->samesite_cookies);
        $httponly = false;

        if (isset(Registry::load('config')->http_only_cookies) && Registry::load('config')->http_only_cookies) {
            $httponly = true;
        }

        if (defined('PHP_VERSION_ID') && PHP_VERSION_ID >= 70300) {
            $cookie_options = array(
                'expires' => $time,
                'path' => $path,
                'secure' => true,
                'httponly' => $httponly,
                'samesite' => $samesite_cookies
            );

            if (isset(Registry::load('config')->cookie_domain) && !empty(Registry::load('config')->cookie_domain)) {
                $cookie_options['domain'] = Registry::load('config')->cookie_domain;
            }


            setcookie($name, $value, $cookie_options);
        } else {
            $samesite_cookies = "$path; SameSite=$samesite_cookies; Secure";
            setcookie($name, $value, $time, $samesite_cookies);
        }
    } else {
        setcookie($name, $value, $time, $path);
    }
}
function redirect($url = null, $external_link = false) {
    if (!$external_link && strpos($url, "http://") !== 0 && strpos($url, "https://") !== 0) {
        $url = Registry::load('config')->site_url.$url;
    }

    $url = htmlspecialchars(trim($url), ENT_QUOTES, 'UTF-8');

    if (headers_sent()) {
        echo '<script type="text/javascript"> document.location = "'.$url.'"; </script>';
        exit;
    } else {
        header('HTTP/1.1 307 Temporary Redirect');
        header('Cache-Control: no-cache, no-store, must-revalidate');
        header('Expires: Thu, 01 Jan 1970 00:00:00 GMT');
        header('Pragma: no-cache');
        header("Location:".$url, true, 307);
        exit;
    }
}

function cleanOutput($value) {
    $value = htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function translate_months($date, $locale = 'es_ES') {
    if (extension_loaded('intl')) {
        $timestamp = strtotime($date);
        $date = new DateTime();
        $date->setTimestamp($timestamp);
        $formatter = new IntlDateFormatter($locale, IntlDateFormatter::LONG, IntlDateFormatter::NONE);
        $formattedDate = $formatter->format($timestamp);

        return $formattedDate;
    } else {
        return $date;
    }
}

function get_date($data = null) {
    $format = 'Y-m-d H:i:s';
    $result = $timeformat = $date = null;

    if (isset($data['format']) && !empty($data['format'])) {
        $format = $data['format'];
    }

    if (isset($data['auto_format']) && $data['auto_format']) {
        if (Registry::load('settings')->dateformat === 'mdy_format') {
            $format = "M-d-Y";
        } elseif (Registry::load('settings')->dateformat === 'ymd_format') {
            $format = "Y-M-d";
        } else {
            $format = "d-M-Y";
        }

        if (isset($data['include_time']) && $data['include_time']) {
            if (Registry::load('settings')->time_format === '24_format') {
                $timeformat = 'H:i';
            } else {
                $timeformat = 'h:i a';
            }
        } elseif (isset($data['time_alone']) && $data['time_alone']) {
            if (Registry::load('settings')->time_format === '24_format') {
                $format = 'H:i';
            } else {
                $format = 'h:i a';
            }
        }
    }

    if (isset($data['date']) && !empty($data['date'])) {
        $date = $data['date'];
    }

    if (isset($data['timezone']) && !empty($data['timezone'])) {
        $datetime = new DateTime($date);
        $timezone = new DateTimeZone($data['timezone']);
        $datetime->setTimezone($timezone);

        $result = $datetime->format($format);

        if (!empty($timeformat)) {
            $previous_result = $result;

            $result = array();
            $result['date'] = $previous_result;
            $result['time'] = $datetime->format($timeformat);

            if ($timeformat === 'h:i a') {
                $find_am_pm = ['am', 'pm'];
                $replace_am_pm = array();
                $replace_am_pm[] = Registry::load('strings')->time_am;
                $replace_am_pm[] = Registry::load('strings')->time_pm;

                $result['time'] = str_replace($find_am_pm, $replace_am_pm, $result['time']);
            }

            if (isset($data['compare_with_today']) && !empty($data['compare_with_today'])) {
                $today = new DateTime();
                $today->setTimezone($timezone);
                $yesterday = date($format, strtotime($today->format('Y-m-d H:i:s')) - (24 * 60 * 60));
                $today = $today->format($format);

                if ($result['date'] == $today) {
                    $result['date'] = 'today';
                } elseif ($result['date'] == $yesterday) {
                    $result['date'] = 'yesterday';
                }
            }
        }
    } else {
        if (isset($data['date']) && !empty($data['date'])) {
            $result = date($format, strtotime($date));
            if (!empty($timeformat)) {
                $previous_result = $result;

                $result = array();
                $result['date'] = $previous_result;
                $result['time'] = date($timeformat, strtotime($date));
            }
        } else {
            $result = date($format);
            if (!empty($timeformat)) {
                $previous_result = $result;

                $result = array();
                $result['date'] = $previous_result;
                $result['time'] = date($timeformat);
            }
        }
    }

    if (isset($result['date'])) {
        if ($result['date'] !== 'today' && $result['date'] !== 'yesterday') {
            if (extension_loaded('intl')) {
                $formatter = new IntlDateFormatter(Registry::load('strings')->iso_code, IntlDateFormatter::LONG, IntlDateFormatter::NONE);
                $timestamp = strtotime($result['date']);

                if (isset($data['auto_format']) && $data['auto_format']) {
                    if (Registry::load('settings')->dateformat === 'mdy_format') {
                        $formatter->setPattern('MMM-d-Y');
                    } elseif (Registry::load('settings')->dateformat === 'ymd_format') {
                        $formatter->setPattern('Y-MMM-d');
                    } else {
                        $formatter->setPattern('dd-MMM-Y');
                    }
                }

                $result['date'] = $formatter->format($timestamp);
            }
        }
    }

    return $result;
}

function get_data($get = 'post') {
    if ($get === 'get') {
        return $_GET;
    } elseif ($get === 'file') {
        return $_FILES;
    } elseif ($get === 'request') {
        return $_REQUEST;
    } else {
        return $_POST;
    }
}

function abbreviateNumber($num) {
    if ($num >= 0 && $num < 1000) {
        $format = floor($num);
        $suffix = '';
    } elseif ($num >= 1000 && $num < 1000000) {
        $format = floor($num / 1000);
        $suffix = 'k';
    } elseif ($num >= 1000000 && $num < 1000000000) {
        $format = floor($num / 1000000);
        $suffix = 'm';
    } elseif ($num >= 1000000000 && $num < 1000000000000) {
        $format = floor($num / 1000000000);
        $suffix = 'b';
    } elseif ($num >= 1000000000000) {
        $format = floor($num / 1000000000000);
        $suffix = 't';
    }

    return !empty($format . $suffix) ? $format . $suffix : 0;
}


function is_ssl() {
    if (isset($_SERVER['HTTP_CF_VISITOR'])) {
        $cf_visitor = json_decode($_SERVER['HTTP_CF_VISITOR']);

        if (isset($cf_visitor->scheme) && $cf_visitor->scheme == 'https') {
            return true;
        }
    } elseif (isset($_SERVER['HTTPS']) && 'on' === strtolower($_SERVER['HTTPS'])) {
        return true;
    } elseif (isset($_SERVER['HTTPS']) && '1' == $_SERVER['HTTPS']) {
        return true;
    } elseif (isset($_SERVER['SERVER_PORT']) && ('443' == $_SERVER['SERVER_PORT'])) {
        return true;
    } elseif (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https') {
        return true;
    } elseif (isset($_SERVER['HTTP_X_FORWARDED_PORT']) && $_SERVER['HTTP_X_FORWARDED_PORT'] == '443') {
        return true;
    }
    return false;
}

function get_url($todo = null) {
    $result = null;
    $non_latin = true;
    $path = $navigation_scope = str_replace('\\', '/', dirname($_SERVER['PHP_SELF']));

    if ($path == '/') {
        $path = '';
    }

    if (!empty($path)) {
        $path = substr($_SERVER["REQUEST_URI"], strlen($path)+1);
    } else {
        $path = substr($_SERVER["REQUEST_URI"], 1);
    }

    if (isset($todo['remove'])) {
        $result = substr(strstr($path, $todo['remove']), strlen($todo['remove']));
    } elseif (isset($todo['page']) && $todo['page']) {
        $result = preg_split('~[^a-z0-9.\\_\\-]~i', $path)[0];
    } elseif (isset($todo['path']) && $todo['path']) {
        $result = $path;
    } else {
        $result = new stdClass();
        if (isset(Registry::load('config')->force_url) && Registry::load('config')->force_url) {
            $result->site_url = Registry::load('config')->site_url;
        } else {
            if (is_ssl()) {
                $result->scheme = 'https://';
            } else {
                $result->scheme = 'http://';
            }

            $result->site_url = $result->scheme.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
            $result->url_path = $path;

            $result->navigation_scope = $navigation_scope;

            if ($non_latin) {
                $url_path = parse_url($path);

                if (isset($url_path['path'])) {
                    $url_path = $url_path['path'];
                } else {
                    $url_path = '';
                }

                $result->current_page = preg_split('/\//', $url_path)[0];
            } else {
                $result->current_page = preg_split('~[^a-z0-9.\\_\\-]~i', $path)[0];
            }

            if (!empty($path)) {
                $result->site_url = substr($result->site_url, 0, -strlen($path));
            }

            if (!filter_var($result->site_url, FILTER_VALIDATE_URL)) {
                $result->site_url = null;
            }
        }
    }

    return $result;
}

function update_user_csrf_token($data = null) {
    include('fns/global/update_user_csrf_token.php');
    return Registry::load('current_user')->csrf_token;
}

function get_image($data) {
    include('fns/global/get_image.php');
    return $result;
}


function get_img_url($data) {
    include('fns/global/get_img_url.php');
    return $result;
}

function language($data) {
    include('fns/global/languages.php');
    return $result;
}

function cache($data) {
    include('fns/global/cache.php');
    return $result;
}

function update_online_statuses() {
    include('fns/global/update_online_statuses.php');
}

function remove_login_sesion_cache($user_id, $force_remove = false) {
    include('fns/global/remove_login_sesion_cache.php');
}

function ws_push($payload) {
    if (Registry::load('settings')->realtime_mode !== 'websocket') {
        return false;
    }

    $ws_host = Registry::load('settings')->websocket_host;
    $ws_port = Registry::load('settings')->websocket_port;

    $tcp_host = Registry::load('settings')->websocket_tcp_host;
    $tcp_port = Registry::load('settings')->websocket_tcp_port;


    $ws_transmit_code_file = 'assets/cache/ws_transmit_code.cache';
    if (file_exists($ws_transmit_code_file)) {
        $ws_transmit_code = trim(file_get_contents($ws_transmit_code_file));
        $payload['ws_transmit_code'] = $ws_transmit_code;
    } else {
        $ws_transmit_code = random_string(['length' => 8]);
        @file_put_contents('assets/cache/ws_transmit_code.cache', $ws_transmit_code);
    }

    $message = json_encode($payload);
    $packed = pack('N', strlen($message)) . $message;

    if (class_exists('Swoole\Client')) {
        try {
            $client = new Swoole\Client(SWOOLE_SOCK_TCP, SWOOLE_SOCK_SYNC);
            $client->set([
                'open_length_check' => true,
                'package_length_type' => 'N',
                'package_length_offset' => 0,
                'package_body_offset' => 4,
                'connect_timeout' => 1,
                'timeout' => 1
            ]);

            if (@$client->connect($tcp_host, $tcp_port, 1)) {
                $client->send($packed);
                $client->close();
                return true;
            } else {
                error_log("Swoole TCP connection failed. Error code: {$client->errCode}");
            }
        } catch (Exception $e) {
            error_log("Swoole TCP client failed: " . $e->getMessage());
        }
    }

    if (function_exists('stream_socket_client')) {
        $context = stream_context_create([
            'socket' => [
                'tcp_nodelay' => true,
            ]
        ]);

        $fp = @stream_socket_client(
            "tcp://{$tcp_host}:{$tcp_port}",
            $errno,
            $errstr,
            1,
            STREAM_CLIENT_CONNECT,
            $context
        );

        if ($fp) {
            stream_set_timeout($fp, 1);
            $result = fwrite($fp, $packed);
            fclose($fp);
            if ($result !== false) {
                return true;
            }
        }
    }

    if (function_exists('fsockopen')) {
        $sock = @fsockopen($tcp_host, $tcp_post, $errno, $errstr, 1);
        if ($sock) {
            stream_set_timeout($sock, 1);
            $result = fwrite($sock, $packed);
            fclose($sock);
            if ($result !== false) {
                return true;
            }
        }
    }

    if (function_exists('curl_init')) {
        $url = "http://{$ws_host}:{$ws_port}";
        $ch = curl_init($url);

        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 3,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'X-TCP-Fallback: true',
                'Content-Length: ' . strlen(json_encode($payload))
            ]
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return ($httpCode >= 200 && $httpCode < 300);
    }

    return false;
}


function detect_csv_delimiter($file) {
    $handle = fopen($file, 'r');
    if (!$handle) return ',';
    $line = fgets($handle);
    fclose($handle);

    $commaCount = substr_count($line, ',');
    $semicolonCount = substr_count($line, ';');
    $tabCount = substr_count($line, "\t");

    if ($commaCount >= max($semicolonCount, $tabCount)) return ',';
    if ($semicolonCount >= max($commaCount, $tabCount)) return ';';
    return "\t";
}

function get_file_cache_timestamp($folderPath = 'assets/js', $regenerate = false) {
    $fileName = 'file_cache_timestamp.cache';

    $folderPath = rtrim($folderPath, '/');

    if (!is_dir($folderPath)) {
        mkdir($folderPath, 0755, true);
    }

    $filePath = $folderPath . '/' . $fileName;

    if (!file_exists($filePath) || $regenerate) {
        $timestamp = time();
        file_put_contents($filePath, $timestamp);
    } else {
        $timestamp = trim(file_get_contents($filePath));
    }

    return $timestamp;
}

function role($data) {
    if (isset($data['group_role_id'])) {
        include('fns/global/group_roles.php');
    } else {
        include('fns/global/site_roles.php');
    }
    return $result;
}