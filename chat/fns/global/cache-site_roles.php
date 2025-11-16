<?php
$siteroles = array();
$columns = [
    'site_roles.site_role_id', 'site_roles.permissions', 'site_roles.site_role_attribute'
];
$roles = DB::connect()->select('site_roles', $columns);
$site_role_attributes = array();
foreach ($roles as $role) {
    $roleid = $role['site_role_id'];
    $attribute = $role['site_role_attribute'];

    $role_permissions = json_decode($role['permissions']);
    $role_permissions->site_role_attribute = $attribute;
    $role['permissions'] = json_encode($role_permissions);

    $siteroles[$roleid] = $role['permissions'];
    $site_role_attributes[$attribute] = $roleid;
}

$cache = json_encode($siteroles);
$cachefile = 'assets/cache/site_roles.cache';

if (file_exists($cachefile)) {
    unlink($cachefile);
}

$cachefile = fopen($cachefile, "w");
fwrite($cachefile, $cache);
fclose($cachefile);


$cache = json_encode($site_role_attributes);
$cachefile = 'assets/cache/site_role_attributes.cache';

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
        
        $redis_key = md5('assets/cache/site_roles.cache');
        $redis->del($redis_key);
        
        $redis_key = md5('assets/cache/site_role_attributes.cache');
        $redis->del($redis_key);

    } catch (Exception $e) {}
}



$result = true;