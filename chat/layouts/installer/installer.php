<?php
/**
* IMPORTANT NOTICE:
*
* Please reconsider before attempting to null, crack, or redistribute this product unlawfully.
* This software is the result of countless hours of hard work, passion, and dedication.
* It is a labor of love, not a commercial cash-grab.
*
* If you find value in this product, please support its continued development
* by using it ethically and legally.
*/

error_reporting(0);

include 'fns/filters/load.php';
include 'fns/sql/Medoo.php';
use Medoo\Medoo;

$result = array();
$result['success'] = false;
$result['error_message'] = 'The input value is invalid';
$result['error_variables'] = [];
$noerror = true;

$currentTimestamp = time();
$formattedTimestamp = date('Y-m-d H:i:s', $currentTimestamp);

if (!isset($data["purchase_code"]) || empty($data["purchase_code"])) {
    $noerror = false;
    $result['error_message'] = 'Enter your Envato Purchase code';
} elseif (!isset($data["discover_grupo"]) || empty($data["discover_grupo"])) {
    $noerror = false;
    $result['error_message'] = 'Please let us know how you discovered Grupo.';
} elseif (!isset($data["database_hostname"]) || empty($data["database_hostname"])) {
    $noerror = false;
    $result['error_message'] = 'Invalid Database Hostname';
} elseif (!isset($data["database_name"]) || empty($data["database_name"])) {
    $noerror = false;
    $result['error_message'] = 'Invalid Database Name';
} elseif (!isset($data["database_username"]) || empty($data["database_username"])) {
    $noerror = false;
    $result['error_message'] = 'Invalid Database Username';
} elseif (!isset($data["email_address"]) || empty($data["email_address"])) {
    $noerror = false;
    $result['error_message'] = 'Invalid Email Address';
} elseif (!filter_var($data["email_address"], FILTER_VALIDATE_EMAIL)) {
    $noerror = false;
    $result['error_message'] = 'Invalid Email Address';
} elseif (!isset($data["username"]) || empty($data["username"])) {
    $noerror = false;
    $result['error_message'] = 'Type in a preferred Username';
} elseif (!isset($data["password"]) || empty($data["password"])) {
    $noerror = false;
    $result['error_message'] = 'Type in a preferred Password';
}

if (!isset($data["database_password"])) {
    $data["database_password"] = '';
}

if (isset($data["database_type"]) && $data["database_type"] === 'mariadb') {
    $data["database_type"] = 'mariadb';
} else {
    $data["database_type"] = 'mysql';
}

if (!isset($data["database_port"]) || empty($data["database_port"])) {
    $data["database_port"] = '3306';
}

if ($noerror) {
    $data['purchase_code'] = trim($data['purchase_code']);

    if (!preg_match('/^[a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12}$/i', $data['purchase_code'])) {
        $noerror = false;
        $result['error_message'] = 'Purchase code is Invalid';
    } else {
        $purchase_code = $data['purchase_code'];

        $ch = curl_init();
        $url = 'aHR0cHM6Ly9iYWV2b3guY29tL21vZHVsZXMvcHVyY2hhc2VfdmFsaWRhdGlvbi8=';
        $url = base64_decode($url);

        $postData = array(
            'purchase_code' => $data['purchase_code'],
            'email_address' => $data['email_address'],
            'site_url' => Registry::load('config')->site_url,
            'discover_grupo' => $data["discover_grupo"],
        );

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);

        $response = curl_exec($ch);

        if (curl_errno($ch)) {
            $noerror = false;

            $postDataString = http_build_query($postData);

            $context = stream_context_create([
                'http' => [
                    'method' => 'POST',
                    'header' => 'Content-Type: application/x-www-form-urlencoded',
                    'content' => $postDataString,
                    'ignore_errors' => true,
                    'ssl' => [
                        'verify_peer' => false,
                        'verify_peer_name' => false,
                    ],
                ],
            ]);
            $response = file_get_contents($url, false, $context);

            if ($response === false) {
                $noerror = false;
                $result['error_message'] = 'Unable to connect to the server to Validate License'.' Error: ' . error_get_last()['message'];
            } else {
                $noerror = true;
            }
        }

        curl_close($ch);

        if (!empty($response)) {
            $variables = json_decode($response);

            $noerror = false;
            $result['error_message'] = 'Invalid Purchase Code';

            $configFile = 'include/config.php';
            $currentConfig = file_get_contents($configFile);

            $pattern = '/\n\s*\$config->pro_version\s*=\s*\'pro\'\s*;/';
            $newConfig = preg_replace($pattern, '', $currentConfig);
            file_put_contents($configFile, $newConfig);

            if (!empty($variables)) {
                if (isset($variables->license_already_used)) {
                    $result['error_message'] = 'It looks like this license is already being used with another website. If you think this is a mistake, just drop us a line at hello@baevox.com — we are happy to help!';
                } else if (isset($variables->license)) {
                    $license_info_file = 'assets/cache/license_record.cache';
                    file_put_contents($license_info_file, $response);

                    if (isset($variables->extended_license)) {
                        $newLine = "\n\$config->pro_version = 'pro';\n";
                        $pattern = '/(\$db_error_mode\s*=\s*PDO::ERRMODE_SILENT\s*;)/';

                        if (preg_match($pattern, $currentConfig, $matches, PREG_OFFSET_CAPTURE)) {
                            $position = $matches[0][1];
                            $newConfig = substr_replace($currentConfig, $newLine, $position, 0);
                            file_put_contents($configFile, $newConfig);
                        }
                    } else {
                        $pattern = '/\n\s*\$config->pro_version\s*=\s*\'pro\'\s*;/';
                        $newConfig = preg_replace($pattern, '', $currentConfig);
                        file_put_contents($configFile, $newConfig);
                    }
                    $noerror = true;
                }
            }
        }

        if ($noerror) {
            if (!empty($data["database_password"])) {
                $pass_pattern = '/^[a-zA-Z0-9@#$%&*!]+$/';
                if (!preg_match($pass_pattern, $data["database_password"])) {
                    $noerror = false;
                    $result['error_message'] = "Database Password should only contain the following characters: alphanumeric characters and the special characters @, #, $, %, &, *, !";
                }
            }

            if (!empty($data["database_name"])) {
                $db_name_pattern = '/^[a-zA-Z0-9_]+$/';
                if (!preg_match($db_name_pattern, $data["database_name"])) {
                    $noerror = false;
                    $result['error_message'] = "Database Name should only contain alphanumeric characters & underscores";
                }
            }

            if (!empty($data["database_username"])) {
                $db_name_pattern = '/^[a-zA-Z0-9_]+$/';
                if (!preg_match($db_name_pattern, $data["database_username"])) {
                    $noerror = false;
                    $result['error_message'] = "Database Username should only contain alphanumeric characters & underscores";
                }
            }
        }

        if ($noerror) {
            try {
                $db_instance = new Medoo([
                    'type' => $data["database_type"],
                    'host' => $data["database_hostname"],
                    'database' => $data["database_name"],
                    'username' => $data["database_username"],
                    'password' => $data["database_password"],
                    'port' => $data["database_port"],
                    'error' => PDO::ERRMODE_SILENT,
                    'charset' => 'utf8mb4',
                    'collation' => 'utf8mb4_general_ci',
                ]);
            } catch (PDOException $exception) {
                $noerror = false;
                $result['error_message'] = 'Invalid Database Credentials';

                if (!empty($exception->getMessage())) {
                    $result['error_message'] = $exception->getMessage();
                }
            }
        }

        if ($noerror) {
            $config_file = 'include/config.php';
            if (is_writable($config_file)) {
                $file_contents = file_get_contents($config_file);
                $file_contents = preg_replace("/'type' => '([^']+(?='))'/", "'type' => '".$data['database_type']."'", $file_contents);
                $file_contents = preg_replace("/'host' => '([^']+(?='))'/", "'host' => '".$data['database_hostname']."'", $file_contents);
                $file_contents = preg_replace("/'database' => '([^']+(?='))'/", "'database' => '".$data['database_name']."'", $file_contents);
                $file_contents = preg_replace("/'username' => '([^']+(?='))'/", "'username' => '".$data['database_username']."'", $file_contents);
                $file_contents = preg_replace("/'password' => '([^']+(?='))'/", "'password' => '".$data['database_password']."'", $file_contents);
                $file_contents = preg_replace("/'port' => '([^']+(?='))'/", "'port' => '".$data['database_port']."'", $file_contents);
                file_put_contents($config_file, $file_contents);
            } else {
                $noerror = false;
                $result['error_message'] = 'Permission Denied : Unable to write to include/config.php file';
            }
        }

        if ($noerror) {
            include('layouts/installer/item_support_register.php');
            $import_sql = file_get_contents('layouts/installer/installer.sql');

            try {
                $db_instance->query($import_sql);
            } catch (PDOException $exception) {
                $noerror = false;
                $result['error_message'] = 'Database Import Failed';
            }
        }

        if ($noerror) {

            sleep(1);

            $data["username"] = sanitize_username($data["username"]);

            if (empty($data["username"])) {
                $data["username"] = 'admin';
            }

            try {
                $db_instance = new Medoo([
                    'type' => $data["database_type"],
                    'host' => $data["database_hostname"],
                    'database' => $data["database_name"],
                    'username' => $data["database_username"],
                    'password' => $data["database_password"],
                    'port' => $data["database_port"],
                    'error' => PDO::ERRMODE_SILENT,
                    'charset' => 'utf8mb4',
                    'collation' => 'utf8mb4_general_ci',
                ]);

                $update_data = array();
                $update_data["email_address"] = $data['email_address'];
                $update_data["username"] = $data['username'];
                $update_data["password"] = password_hash($data['password'], PASSWORD_BCRYPT);
                $update_data["encrypt_type"] = 'php_password_hash';
                $update_data["salt"] = '';

                $update_data["created_on"] = $formattedTimestamp;
                $update_data["updated_on"] = $formattedTimestamp;

                $db_instance->update("gr_site_users", $update_data, ["OR" => ["username" => "admin", "user_id" => 1]]);
            } catch (PDOException $exception) {
                $data["username"] = 'admin';
                $data["password"] = 'pass';
            }

            $api_secret_key = random_string('15');
            $db_instance->update("gr_settings", ['value' => $api_secret_key], ["setting" => "api_secret_key"]);


            $cs_where = ['string_constant[~]' => 'custom_page_%'];
            $cs_columns = ['string_id', 'string_constant'];
            $custom_page_contents = $db_instance->select("gr_language_strings", $cs_columns, $cs_where);
            $string_ids = array();
            $string_value = '[YOU CAN MODIFY THE PAGE CONTENTS VIA CUSTOM PAGES MODULE]';

            foreach ($custom_page_contents as $custom_page_content) {
                $string_constant = $custom_page_content['string_constant'];
                if (strpos($string_constant, '_content') !== false) {
                    $string_ids[] = $custom_page_content['string_id'];
                }
            }
            if (!empty($string_ids)) {
                $db_instance->update("gr_language_strings", ['string_value' => $string_value], ["string_id" => $string_ids]);
            }

            if (file_exists('layouts/installer/cache_rebuild.php')) {
                include 'layouts/installer/cache_rebuild.php';
            }

            if (file_exists('pages/installer.php')) {
                $rename_to = 'pages/installer_'.strtolower(random_string('10'));
                '.php';
                rename('pages/installer.php', $rename_to);
            }

            if (file_exists('htaccess.backup')) {
                unlink('htaccess.backup');
            }

            if (file_exists('assets/cache/ws_transmit_code.cache')) {
                unlink('assets/cache/ws_transmit_code.cache');
            }

            if (file_exists('assets/cache/total_cloud_storage_size.cache')) {
                unlink('assets/cache/total_cloud_storage_size.cache');
            }

            $robotsTxtContent = "User-agent: *\n";
            $robotsTxtContent .= "Disallow: \n";
            $robotsTxtContent .= "Disallow: /cgi-bin/\n";
            $robotsTxtContent .= "Disallow: /*?*login_session_id=\n";
            $robotsTxtContent .= "Disallow: /*?*session_time_stamp=\n";
            $robotsTxtContent .= "Disallow: /*?*access_code=\n";
            $robotsTxtContent .= "Sitemap: " . Registry::load('config')->site_url . "sitemap/";

            $robotsTxtFilePath = 'robots.txt';
            file_put_contents($robotsTxtFilePath, $robotsTxtContent);

            $result = array();
            $result['success'] = true;
            $result['alert_message'] = "Installation Complete. \n\nYour Login Details:\n";
            $result['alert_message'] .= "Username : ".$data["username"]."\n";
            $result['alert_message'] .= "Password : ".strip_tags($data["password"])."\n";
        }
    }
}

$result = json_encode($result);
echo $result;