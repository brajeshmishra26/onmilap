<?php

if (role(['permissions' => ['super_privileges' => 'firewall']])) {

    $form = array();
    $form['loaded'] = new stdClass();
    $form['loaded']->title = Registry::load('strings')->firewall;
    $form['loaded']->button = Registry::load('strings')->update;

    $form['fields'] = new stdClass();

    $form['fields']->update = [
        "tag" => 'input', "type" => 'hidden', "class" => 'd-none', "value" => "firewall"
    ];


    $form['fields']->firewall_notes = [
        "title" => Registry::load('strings')->please_note, "tag" => 'textarea', "class" => 'field',
        "value" => Registry::load('strings')->firewall_notes, "attributes" => ['disabled' => 'disabled']
    ];


    $form['fields']->blacklist = [
        "title" => Registry::load('strings')->blacklist, "tag" => 'input', "type" => "text", "class" => 'field',
        "placeholder" => Registry::load('strings')->enter_ip_address, "clone_field_on_input" => true,
    ];

}

?>