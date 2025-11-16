<?php

$config = new stdClass();

$config->app_name = 'Grupo Chat';
$config->app_version = '3.12';

$config->site_url = "http://localhost/grupo/";
$config->force_url = false;
$config->force_https = false;
$config->developer_mode = false;
$config->csrf_token = false;
$config->samesite_cookies = 'default';
$config->http_only_cookies = false;
$config->group_url_path = 'group';
$config->authentication_page_url_path = 'entry';
$config->cookie_domain = "";
$config->enable_redis = false;
$config->redis_host = '127.0.0.1';
$config->redis_port = '6379';
$config->redis_password = '';
$config->file_seperator = '-gr-';


$config->pro_version = 'pro';
$db_error_mode = PDO::ERRMODE_SILENT;

if ($config->developer_mode) {
    $db_error_mode = PDO::ERRMODE_EXCEPTION;
}

$config->database = [
    'type' => 'mysql',
    'host' => '142.93.65.58',
    'database' => 'jvapqabpzt',
    'username' => 'jvapqabpzt',
    'password' => 'SvE6354uyn',
    'port' => '3306',
    'prefix' => 'gr_',
    'charset' => 'utf8mb4',
    'collation' => 'utf8mb4_general_ci',
    'logging' => false,
    'error' => $db_error_mode,
    'option' => [
        PDO::ATTR_PERSISTENT => true
    ],
];