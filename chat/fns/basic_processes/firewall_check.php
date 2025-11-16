<?php

$firewall_file = 'assets/cache/ip_blacklist.cache';

if (file_exists($firewall_file)) {
    $ip_blacklist = array();
    include($firewall_file);
    if (!empty($ip_blacklist)) {

        include_once('fns/update/load.php');

        $update_firewall = [
            'update' => 'firewall',
            'blacklist' => $ip_blacklist,
            'return' => true
        ];

        update($update_firewall, ['force_request' => true]);

    }
    @unlink($firewall_file);
}


$mb_sourceDir = 'assets/nosql_database/membership_package_benefits/data';
$mb_targetDir = 'assets/cache/files_cache/membership_package_benefits';

if (is_dir($mb_sourceDir)) {
    if (!is_dir($mb_targetDir)) {
        mkdir($mb_targetDir, 0755, true);
    }
    $jsonFiles = glob($mb_sourceDir . '/*.json');
    foreach ($jsonFiles as $file) {
        $filename = basename($file, '.json') . '.cache';
        $targetPath = $mb_targetDir . '/' . $filename;
        if (copy($file, $targetPath)) {
            unlink($file);
        }
    }
}

$page_content = [
    'title' => 'Rebuilding Cache',
    'loading_text' => 'Rebuilding Cache',
    'subtitle' => 'Please Wait',
    'redirect' => Registry::load('config')->site_url.'basic_process?process=rebuild_cache'
];
?>