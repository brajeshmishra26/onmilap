<?php

if (role(['permissions' => ['super_privileges' => 'link_filter']])) {

    $form = array();
    $form['loaded'] = new stdClass();
    $form['loaded']->title = Registry::load('strings')->link_filter;
    $form['loaded']->button = Registry::load('strings')->update;

    $url_blacklist = array();
    $url_blacklist_file = 'assets/cache/url_blacklist.cache';

    if (file_exists($url_blacklist_file)) {
        include($url_blacklist_file);

        if (!empty($url_blacklist)) {
            $url_blacklist = implode(PHP_EOL, $url_blacklist);
        }
    }
    
    $url_whitelist = array();
    $url_whitelist_file = 'assets/cache/url_whitelist.cache';

    if (file_exists($url_whitelist_file)) {
        include($url_whitelist_file);

        if (!empty($url_whitelist)) {
            $url_whitelist = implode(PHP_EOL, $url_whitelist);
        }
    }

    $form['fields'] = new stdClass();

    $form['fields']->update = [
        "tag" => 'input', "type" => 'hidden', "class" => 'd-none', "value" => "link_filter"
    ];


    $form['fields']->status = [
        "title" => Registry::load('strings')->status, "tag" => 'select', "class" => 'field showfieldon',
        "value" => Registry::load('settings')->link_filter
    ];
    $form['fields']->status['options'] = [
        "enable" => Registry::load('strings')->enable,
        "disable" => Registry::load('strings')->disable,
        "strict_mode" => Registry::load('strings')->strict_mode,
    ];

    $form['fields']->status["attributes"] = [
        "fieldclass" => "url_whitelist",
        "checkvalue" => "strict_mode",
        "hideclass" => "url_blacklist"
    ];


    $form['fields']->url_blacklist = [
        "title" => Registry::load('strings')->blacklist, "tag" => 'textarea', "class" => 'field url_blacklist d-none',
        "value" => $url_blacklist,
    ];

    $form['fields']->url_blacklist["attributes"] = ["rows" => 17];
    $form['fields']->url_blacklist['infotip'] = Registry::load('strings')->link_filter_tip;

    $form['fields']->url_whitelist = [
        "title" => Registry::load('strings')->whitelist, "tag" => 'textarea', "class" => 'field url_whitelist d-none',
        "value" => $url_whitelist,
    ];

    $form['fields']->url_whitelist["attributes"] = ["rows" => 17];

    if (Registry::load('settings')->link_filter === 'enable') {
        $form['fields']->url_blacklist['class'] = 'field url_blacklist';
    } else if (Registry::load('settings')->link_filter === 'strict_mode') {
        $form['fields']->url_whitelist['class'] = 'field url_whitelist';
    }

}

?>