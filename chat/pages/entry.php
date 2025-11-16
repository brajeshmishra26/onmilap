<?php
include 'fns/firewall/load.php';
include 'fns/sql/load.php';
include 'fns/variables/load.php';
include 'fns/global/um_mode.php';

if (Registry::load('settings')->minify_html_output === 'yes') {
    $ob_enabled = ini_get('output_buffering');

    if ($ob_enabled) {
        ob_start('minify_output');
    } else {
        Registry::load('settings')->minify_html_output = 'no';
    }
}

include 'layouts/entry_page/layout.php';
if (Registry::load('settings')->minify_html_output === 'yes') {
    ob_end_flush();
}
?>