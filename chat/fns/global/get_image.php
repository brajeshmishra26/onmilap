<?php

$result = null;
$separator = Registry::load('config')->file_seperator;
$search_directory = 'assets/files/'.$data['from'].'/';
$search_pattern = $data['search'].$separator;
$allowed_extensions = ['jpg', 'png', 'gif', 'jpeg', 'bmp'];
$found = false;
$include_site_url = !isset($data['exclude_site_url']) || !$data['exclude_site_url'];
$replace_with_default = isset($data['replace_with_default']) ? $data['replace_with_default'] : true;

if (is_dir($search_directory)) {
    $iterator = new FilesystemIterator($search_directory);

    foreach ($iterator as $file) {
        $file_name = $file->getFilename();
        $ext = strtolower($file->getExtension());

        if (strpos($file_name, $search_pattern) === 0 && in_array($ext, $allowed_extensions)) {
            $found = true;
            if (isset($data['exists']) && $data['exists']) {
                $result = true;
            } else {
                $result = $file->getPathname();

                if ($include_site_url) {
                    $result = Registry::load('config')->site_url . $file->getPathname();
                }
            }
            break;
        }
    }
}

if (!$found) {
    if (isset($data['exists']) && $data['exists']) {
        $result = false;
    } else {
        $default_path = 'assets/files/'.$data['from'].'/default.png';
        if ($replace_with_default) {
            $result = $include_site_url ? Registry::load('config')->site_url . $default_path : $default_path;
        } else {
            $result = null;
        }
    }
}

if (!$found && Registry::load('settings')->gravatar === 'enable') {
    if (isset($data['gravatar']) && filter_var($data['gravatar'], FILTER_VALIDATE_EMAIL)) {
        $result = 'https://www.gravatar.com/avatar/' . md5(strtolower(trim($data['gravatar']))) . "?s=150&d=mp&r=g";
    }
}