<?php

include 'fns/registry/load.php';
include 'fns/data_cache/load.php';
include 'include/config.php';

Registry::__init();
$config->ws_instance = true;

date_default_timezone_set('Asia/Kolkata');

include 'fns/global/load.php';

$sys_settings = extract_json(['file' => 'assets/cache/settings.cache']);
$url_info = $sys_settings->site_address;

$config = (object) array_merge((array)$config, (array)$url_info);
Registry::add('config', $config);
Registry::add('settings', $sys_settings);
$GLOBALS['swoole_worker_id'] = null;

include_once 'fns/sql/swoole.php';

$ws_max_connections = $sys_settings->websocket_max_con;
$ws_host = $sys_settings->websocket_host;
$ws_port = $sys_settings->websocket_port;
$ws_protocol = $sys_settings->websocket_protocol;
$ws_ssl_certificate = $sys_settings->websocket_ssl_certificate;
$ws_private_key = $sys_settings->websocket_private_key;

$tcp_host = Registry::load('settings')->websocket_tcp_host;
$tcp_port = Registry::load('settings')->websocket_tcp_port;

$languageCache = [];
$languageCacheTimestamps = [];
$permissionsCache = [];
$permissionsCacheTimestamps = [];
$cacheTTL = 300;

$ws_clients = new Swoole\Table($ws_max_connections);
$ws_clients->column('user_id', Swoole\Table::TYPE_INT);
$ws_clients->column('fd', Swoole\Table::TYPE_INT);
$ws_clients->column('info', Swoole\Table::TYPE_STRING, 3072);
$ws_clients->column('chat_context', Swoole\Table::TYPE_STRING, 1024);
$ws_clients->column('userFpToken', Swoole\Table::TYPE_STRING, 255);
$ws_clients->create();

$ws_users = new Swoole\Table($ws_max_connections);
$ws_users->column('fds', Swoole\Table::TYPE_STRING, 256);
$ws_users->create();

$monitoring_private_chat = [];

$ws_instance = new Swoole\Websocket\Server($ws_host, $ws_port ?: 9502);

if ($ws_protocol === 'ssl') {
    $ws_instance->set([
        'ssl_cert_file' => $ws_ssl_certificate,
        'ssl_key_file' => $ws_private_key,
    ]);
}

$ws_instance->set([
    'max_request' => 0,
    'reload_async' => true,
    'max_wait_time' => 120,
    'heartbeat_check_interval' => 60,
    'dispatch_mode' => 2,
    'enable_coroutine' => true,
    'open_tcp_nodelay' => true,
    'heartbeat_idle_time' => 600,
    // 'worker_num' => min(16, swoole_cpu_num() * 2),
    'max_connection' => $ws_max_connections,
    'tcp_fastopen' => true,
]);



$tcp_listener = $ws_instance->addListener($tcp_host, $tcp_port, SWOOLE_SOCK_TCP);
$tcp_listener->set([
    'open_length_check' => true,
    'package_length_type' => 'N',
    'package_length_offset' => 0,
    'package_body_offset' => 4,
]);

DB::connect()->update('site_users', ['online_status' => 0], ['online_status' => 1]);

function getLanguageStrings($lang_id, $ttl = 300) {
    global $languageCache, $languageCacheTimestamps;

    $now = time();
    if (!isset($languageCache[$lang_id]) || ($now - ($languageCacheTimestamps[$lang_id] ?? 0)) > $ttl) {
        $languageCache[$lang_id] = extract_json([
            'file' => 'assets/cache/languages/language-' . $lang_id . '.cache'
        ]);
        $languageCacheTimestamps[$lang_id] = $now;
    }
    return $languageCache[$lang_id];
}

function getRolePermissions($role_id, $ttl = 300) {
    global $permissionsCache, $permissionsCacheTimestamps;

    $now = time();
    if (!isset($permissionsCache[$role_id]) || ($now - ($permissionsCacheTimestamps[$role_id] ?? 0)) > $ttl) {
        $permissionsCache[$role_id] = extract_json([
            'file' => 'assets/cache/site_roles.cache',
            'extract' => $role_id
        ]);
        $permissionsCache[$role_id]['role_hierarchy'] = $permissionsCache[$role_id]['role_hierarchy'] ?? 1;
        $permissionsCache[$role_id]['site_role_attribute'] = $permissionsCache[$role_id]['site_role_attribute'] ?? 'custom_site_role';
        $permissionsCacheTimestamps[$role_id] = $now;
    }
    return $permissionsCache[$role_id];
}

$ws_instance->on("open", function ($ws_instance, $request) use (&$ws_clients, &$ws_users) {

    $login_session_id = $request->get['login_session_id'] ?? null;
    $access_code = $request->get['access_code'] ?? null;
    $session_time_stamp = $request->get['session_time_stamp'] ?? null;

    include 'fns/variables/load.php';

    $ws_clients->set($request->fd, [
        'fd' => $request->fd,
        'user_id' => (int)Registry::load('current_user')->id,
        'info' => json_encode(Registry::load('current_user')),
        'userFpToken' => null,
        'chat_context' => json_encode([]),
    ]);

    if (Registry::load('current_user')->logged_in && !empty(Registry::load('current_user')->username)) {

        $user_id = Registry::load('current_user')->id;

        if (!$ws_users->exist($user_id)) {
            $ws_users->set($user_id, ['fds' => '[]']);
        }

        $current_fds = json_decode($ws_users->get($user_id)['fds'], true);

        if (!in_array($request->fd, $current_fds)) {
            $current_fds[] = $request->fd;
            $ws_users->set($user_id, ['fds' => json_encode($current_fds)]);
        }

        DB::connect()->update('site_users', [
            'online_status' => 1,
            "last_seen_on" => Registry::load('current_user')->time_stamp,
            "updated_on" => Registry::load('current_user')->time_stamp,
        ], ['user_id' => $user_id]);

        $ws_data = ['update' => 'online_users'];
        include 'fns/realtime/ws_process_data.php';
    }

});

$tcp_listener->on("receive", function (Swoole\Server $server, int $tcp_fd, int $reactor_id, string $tcp_data) use (&$ws_instance, &$ws_clients, &$ws_users, &$monitoring_private_chat) {
    try {
        $tcp_data = trim(substr($tcp_data, 4));
        $ws_data = json_decode($tcp_data, true);

        if (!empty($ws_data) && isset($ws_data['ws_transmit_code'])) {
            $ws_transmit_code_file = 'assets/cache/ws_transmit_code.cache';
            if (file_exists($ws_transmit_code_file)) {
                $ws_transmit_code = trim(file_get_contents($ws_transmit_code_file));
                if ($ws_data['ws_transmit_code'] === $ws_transmit_code && isset($ws_data['update'])) {
                    include 'fns/realtime/ws_process_data.php';
                } else {
                    echo "HTTP : Invalid Transmit Code" . "\n";
                }
            }
        }
    } catch (Exception $e) {
        echo "WebSocket Message Error: " . $e->getMessage() . PHP_EOL;
    } finally {
        DB::reset($GLOBALS['swoole_worker_id'] ?? 0);
    }
});


$ws_instance->on("request", function ($request, $response) use (&$ws_instance, &$ws_clients, &$ws_users, &$monitoring_private_chat) {
    try {

        $ws_data = json_decode($request->rawContent(), true);
        if (isset($ws_data['ws_transmit_code'])) {
            $ws_transmit_code_file = 'assets/cache/ws_transmit_code.cache';
            if (file_exists($ws_transmit_code_file)) {
                $ws_transmit_code = trim(file_get_contents($ws_transmit_code_file));
                if ($ws_data['ws_transmit_code'] === $ws_transmit_code && isset($ws_data['update'])) {
                    include 'fns/realtime/ws_process_data.php';
                } else {
                    echo "HTTP : Invalid Transmit Code". "\n";
                }
            }
        }
    } catch (Exception $e) {
        echo "WebSocket Message Error: " . $e->getMessage() . PHP_EOL;
    }
});

$ws_instance->on("message", function ($ws_instance, $frame) use (&$ws_clients, &$ws_users, &$monitoring_private_chat) {
    try {
        $ws_data = json_decode($frame->data, true);
        $current_fd = $frame->fd;

        if (isset($ws_data['type']) && $ws_data['type'] === "chat_context" && isset($ws_data['payload'])) {
            $payload = $ws_data['payload'];
            $process_ws_data = false;

            $current_client_data = $ws_clients->get($current_fd);
            $prev_chat_context = json_decode($current_client_data['chat_context'] ?? '{}', true);



            if (isset($payload["userFpToken"]) && empty($current_client_data['userFpToken'])) {
                $ws_data = ['update' => 'user_fp_token'];
                $process_ws_data = true;
            }

            if (isset($payload['user_id']) && $payload['user_id'] === 'all') {
                $monitoring_private_chat[$current_fd] = $current_client_data['user_id'];
            } else {
                unset($monitoring_private_chat[$current_fd]);
            }

            if (isset($payload['user_id'], $prev_chat_context['user_id']) &&
                (int)$payload['user_id'] !== (int)$prev_chat_context['user_id']
            ) {
                $ws_data = ['update' => 'last_seen_by_recipient', 'receiver_id' => $payload['user_id'], 'sender_id' => $current_client_data['user_id']];
                $process_ws_data = true;
            } elseif (isset($payload['group_id'], $prev_chat_context['group_id']) &&
                (int)$payload['group_id'] !== (int)$prev_chat_context['group_id']
            ) {
                $ws_data = ['update' => 'new_group_video_call'];
                $process_ws_data = true;
            }

            if ($process_ws_data) {
                include 'fns/realtime/ws_process_data.php';
            }
            $current_client_data['chat_context'] = json_encode($payload);
            $ws_clients->set($current_fd, $current_client_data);
        }
    } catch (Exception $e) {
        echo "WebSocket Message Error: " . $e->getMessage() . PHP_EOL;
    }
});

$ws_instance->on('WorkerStart', function ($server, $workerId) {
    $GLOBALS['swoole_worker_id'] = $workerId;
});

$ws_instance->on('WorkerExit', function ($server, $workerId) {
    $GLOBALS['swoole_worker_id'] = $workerId;
    DB::closeConnection($workerId);
});


$ws_instance->on("close", function ($ws_instance, $current_fd) use (&$ws_clients, &$ws_users, &$monitoring_private_chat) {
    if (!$ws_clients->exist($current_fd)) {
        return;
    }

    $client = $ws_clients->get($current_fd);
    $user_id = $client['user_id'];

    if ($ws_users->exist($user_id)) {
        $current_fds = json_decode($ws_users->get($user_id)['fds'], true);
        $current_fds = array_filter($current_fds, fn($clientFd) => $clientFd !== $current_fd);
        if (!empty($current_fds)) {
            $ws_users->set($user_id, ['fds' => json_encode($current_fds)]);
        } else {
            $ws_users->del($user_id);
        }
    }


    $ws_clients->del($current_fd);

    if (isset($monitoring_private_chat[$current_fd])) {
        unset($monitoring_private_chat[$current_fd]);
    }

    DB::connect()->update('site_users', [
        'online_status' => 0,
        "last_seen_on" => Registry::load('current_user')->time_stamp,
        "updated_on" => Registry::load('current_user')->time_stamp,
    ], ['user_id' => $user_id]);

    $ws_data = ['update' => 'online_users'];
    include 'fns/realtime/ws_process_data.php';
});

if (isset(Registry::load('config')->pro_version) && !empty(Registry::load('config')->pro_version)) {
    Swoole\Timer::tick(1800000, function() {
        include('fns/realtime/sys_validate_memberships.php');
    });
}

$ws_instance->start();