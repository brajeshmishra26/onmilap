<?php

if (role(['permissions' => ['site_adverts' => 'create']]) || role(['permissions' => ['site_adverts' => 'edit']])) {
    $form = array();

    $todo = 'add';
    $form['loaded'] = new stdClass();
    $form['fields'] = new stdClass();

    if (isset($load["site_advert_id"])) {

        $load["site_advert_id"] = filter_var($load["site_advert_id"], FILTER_SANITIZE_NUMBER_INT);

        $todo = 'update';

        $columns = [
            'site_advertisements.site_advert_name', 'site_advertisements.disabled',
            'site_advertisements.site_advert_placement', 'site_advertisements.site_advert_max_height',
            'site_advertisements.site_advert_content', 'site_advertisements.site_advert_min_height',
            'site_advertisements.site_role_restricted'
        ];

        $where["site_advertisements.site_advert_id"] = $load["site_advert_id"];
        $where["LIMIT"] = 1;

        $advert = DB::connect()->select('site_advertisements', $columns, $where);

        if (!isset($advert[0])) {
            return false;
        } else {
            $advert = $advert[0];
        }

        $form['fields']->site_advert_id = [
            "tag" => 'input', "type" => 'hidden', "class" => 'd-none', "value" => $load["site_advert_id"]
        ];

        $form['loaded']->title = Registry::load('strings')->edit_advert;
        $form['loaded']->button = Registry::load('strings')->update;
    } else {
        $form['loaded']->title = Registry::load('strings')->create_advert;
        $form['loaded']->button = Registry::load('strings')->create;
    }

    $form['fields']->$todo = [
        "tag" => 'input', "type" => 'hidden', "class" => 'd-none', "value" => "site_adverts"
    ];

    $form['fields']->advert_name = [
        "title" => Registry::load('strings')->advert_name, "tag" => 'input', "type" => "text", "class" => 'field',
        "placeholder" => Registry::load('strings')->advert_name,
    ];

    $form['fields']->advert_min_height = [
        "title" => Registry::load('strings')->advert_min_height, "tag" => 'input', "type" => "number", "class" => 'field',
        "placeholder" => Registry::load('strings')->advert_min_height,
    ];

    $form['fields']->advert_min_height["value"] = 150;

    $form['fields']->advert_max_height = [
        "title" => Registry::load('strings')->advert_max_height, "tag" => 'input', "type" => "number", "class" => 'field',
        "placeholder" => Registry::load('strings')->advert_max_height,
    ];

    $form['fields']->advert_max_height["value"] = 150;

    $form['fields']->advert_placement = [
        "title" => Registry::load('strings')->advert_placement, "tag" => 'select', "class" => 'field'
    ];

    $form['fields']->advert_placement['options'] = [
        "left_content_block" => Registry::load('strings')->left_content_block,
        "info_panel" => Registry::load('strings')->info_panel,
        "welcome_screen" => Registry::load('strings')->welcome_screen,
        "chat_page_header" => Registry::load('strings')->chat_page_header,
        "chat_page_footer" => Registry::load('strings')->chat_page_footer,
        "entry_page_form_header" => Registry::load('strings')->entry_page_form_header,
        "entry_page_form_footer" => Registry::load('strings')->entry_page_form_footer,
        "landing_page_groups_section" => Registry::load('strings')->landing_page_groups_section,
        "landing_page_faq_section" => Registry::load('strings')->landing_page_faq_section,
    ];

    $form['fields']->advert_content = [
        "title" => Registry::load('strings')->advert_content, "tag" => 'textarea',
        "class" => 'field page_content code_editor',
        "placeholder" => Registry::load('strings')->advert_content
    ];

    $form['fields']->advert_content["attributes"] = ["rows" => 6, "id" => "form_code_editor"];

    $form['fields']->site_role_restricted = [
        "title" => Registry::load('strings')->site_role_restricted, "tag" => 'select', "class" => 'field showfieldon'
    ];

    $form['fields']->site_role_restricted["attributes"] = ["fieldclass" => "role_restricted", "checkvalue" => "yes"];

    $form['fields']->site_role_restricted['options'] = [
        "yes" => Registry::load('strings')->yes,
        "no" => Registry::load('strings')->no,
    ];

    $language_id = Registry::load('current_user')->language;

    $join = ["[>]language_strings(string)" => ["site_roles.string_constant" => "string_constant", "AND" => ["language_id" => $language_id]]];
    $columns = ['site_roles.site_role_id', 'string.string_value(name)'];
    $where = ['site_roles.site_role_id[!]' => Registry::load('site_role_attributes')->banned_users];

    $site_roles = DB::connect()->select('site_roles', $join, $columns, $where);

    $site_roles = array_column($site_roles, 'name', 'site_role_id');

    $form['fields']->site_roles = [
        "title" => Registry::load('strings')->site_roles, "tag" => 'checkbox',
        "class" => 'field role_restricted d-none', 'options' => $site_roles, 'select_all' => true
    ];

    $form['fields']->disabled = [
        "title" => Registry::load('strings')->disabled, "tag" => 'select', "class" => 'field'
    ];
    $form['fields']->disabled['options'] = [
        "yes" => Registry::load('strings')->yes,
        "no" => Registry::load('strings')->no,
    ];

    if (isset($load["site_advert_id"])) {
        $site_role_restricted = $disabled = 'no';

        if ((int)$advert['disabled'] === 1) {
            $disabled = 'yes';
        }

        unset($form['fields']->advert_content["placeholder"]);

        $form['fields']->advert_name["value"] = $advert['site_advert_name'];

        if (empty($advert['site_advert_min_height'])) {
            $advert['site_advert_min_height'] = 0;
        }

        if ((int)$advert['site_role_restricted'] === 1) {
            $site_role_restricted = 'yes';
            $form['fields']->site_roles["class"] = 'field role_restricted';
        }

        $form['fields']->advert_placement["value"] = $advert['site_advert_placement'];
        $form['fields']->advert_min_height["value"] = $advert['site_advert_min_height'];
        $form['fields']->advert_max_height["value"] = $advert['site_advert_max_height'];
        $form['fields']->advert_content["value"] = htmlspecialchars($advert['site_advert_content'], ENT_QUOTES, 'UTF-8');
        $form['fields']->disabled["value"] = $disabled;
        $form['fields']->site_role_restricted["value"] = $site_role_restricted;

        $columns = ['site_role_id'];
        $where = ['site_advert_id' => $load["site_advert_id"]];
        $restricted_roles = DB::connect()->select('site_advertisements_roles', $columns, $where);

        $restricted_roles = array_map(function($item) {
            return (string) $item['site_role_id'];
        }, $restricted_roles);

        $form['fields']->site_roles["values"] = $restricted_roles;

    }
}
?>