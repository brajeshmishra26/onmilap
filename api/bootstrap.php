<?php

if (!defined('APP_BOOTSTRAPPED')) {
    define('APP_BOOTSTRAPPED', true);

    $chatRoot = realpath(__DIR__ . '/../chat');
    if ($chatRoot !== false && is_dir($chatRoot)) {
        chdir($chatRoot);
        if (!defined('CHAT_APP_ROOT')) {
            define('CHAT_APP_ROOT', $chatRoot);
        }
    }

    require_once __DIR__ . '/../chat/fns/registry/load.php';
    require_once __DIR__ . '/../chat/fns/data_cache/load.php';
    require_once __DIR__ . '/../chat/include/config.php';

    Registry::__init();
    Registry::add('config', $config);

    date_default_timezone_set('Asia/Kolkata');

    require_once __DIR__ . '/../chat/fns/firewall/load.php';
    require_once __DIR__ . '/../chat/fns/global/load.php';
    require_once __DIR__ . '/../chat/fns/sql/load.php';
    require_once __DIR__ . '/../chat/fns/variables/load.php';
    require_once __DIR__ . '/../chat/fns/mailer/load.php';
    require_once __DIR__ . '/../includes/subscription/bootstrap.php';
}
