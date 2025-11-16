<?php
$sys_tasks_cache = 'assets/cache/sys_tasks_time.cache';
$sys_timeThreshold = 5 * 60;

if (!file_exists($sys_tasks_cache)) {
    file_put_contents($sys_tasks_cache, $current_user_id);
    $result['sys_tasks'] = $escape = true;
} else {
    $fileCreationTime = filectime($sys_tasks_cache);
    $currentTime = time();

    if (($currentTime - $fileCreationTime) > $sys_timeThreshold) {
        unlink($sys_tasks_cache);
        file_put_contents($sys_tasks_cache, $current_user_id);
        $result['sys_tasks'] = $escape = true;
    }
}

if ($data["sys_tasks"] === 'execute') {

    if (isset(Registry::load('config')->pro_version) && !empty(Registry::load('config')->pro_version)) {
        include('fns/realtime/sys_validate_memberships.php');
    }

    exit;
}