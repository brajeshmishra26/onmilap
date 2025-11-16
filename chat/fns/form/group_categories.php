<?php

if (role(['permissions' => ['super_privileges' => 'manage_group_categories']])) {

    $todo = 'add';

    $form['loaded'] = new stdClass();
    $form['fields'] = new stdClass();

    if (isset($load["group_category_id"])) {

        $load["group_category_id"] = filter_var($load["group_category_id"], FILTER_SANITIZE_NUMBER_INT);
        $language_id = Registry::load('current_user')->language;

        if (empty($load["group_category_id"])) {
            return;
        }

        $columns = $where = $join = null;
        $columns = [
            'languages.name', 'languages.language_id'
        ];

        $where["languages.language_id[!]"] = null;

        $languages = DB::connect()->select('languages', $columns, $where);


        $columns = $where = $join = null;
        $columns = [
            'group_categories.group_category_id', 'group_categories.category_name',
            'group_categories.disabled', 'group_categories.group_category_image',
            'group_categories.category_order', 'group_categories.access_restricted'
        ];

        $where["group_categories.group_category_id"] = $load["group_category_id"];
        $where["LIMIT"] = 1;

        $group_category = DB::connect()->select('group_categories', $columns, $where);

        if (isset($group_category[0])) {
            $group_category = $group_category[0];
        } else {
            return;
        }

        if (isset($load["language_id"])) {
            $load["language_id"] = filter_var($load["language_id"], FILTER_SANITIZE_NUMBER_INT);

            if (!empty($load["language_id"])) {
                $language_id = $load["language_id"];
            }
        }


        $form['fields']->group_category_id = [
            "tag" => 'input', "type" => 'hidden', "class" => 'd-none', "value" => $load["group_category_id"]
        ];

        $todo = 'update';
        $form['loaded']->title = Registry::load('strings')->edit_category;
        $form['loaded']->button = Registry::load('strings')->update;


        $form['fields']->group_category_identifier = [
            "title" => Registry::load('strings')->group_category_id, "tag" => 'input', "type" => 'text', "class" => 'field',
            "attributes" => ["disabled" => "disabled"],
            "value" => $load["group_category_id"],
        ];

        $form['fields']->language_id = [
            "title" => Registry::load('strings')->language, "tag" => 'select', "class" => 'field',
        ];

        $form['fields']->language_id["class"] = 'field switch_form';

        if (isset($load["language_id"]) && !empty($load["language_id"])) {
            $form['fields']->language_id['value'] = $language_id;
        }

        $form['fields']->language_id["parent_attributes"] = [
            "form" => "group_categories",
            "data-group_category_id" => $load["group_category_id"],
        ];

        foreach ($languages as $language) {
            $language_identifier = $language['language_id'];
            $form['fields']->language_id['options'][$language_identifier] = $language['name'];
        }

    } else {
        $form['loaded']->title = Registry::load('strings')->create_category;
        $form['loaded']->button = Registry::load('strings')->create;
    }

    $form['fields']->$todo = [
        "tag" => 'input', "type" => 'hidden', "class" => 'd-none', "value" => "group_categories"
    ];

    $form['fields']->category_name = [
        "title" => Registry::load('strings')->category_name, "tag" => 'input', "type" => "text",
        "class" => 'field', "placeholder" => Registry::load('strings')->category_name,
        "required" => true
    ];


    $form['fields']->category_image = [
        "title" => Registry::load('strings')->category_image, "tag" => 'input', "type" => 'file', "class" => 'field filebrowse',
        "accept" => 'image/png,image/x-png,image/gif,image/jpeg,image/webp'
    ];

    $form['fields']->category_order = [
        "title" => Registry::load('strings')->category_order, "tag" => 'input', "type" => 'number', "class" => 'field',
        "placeholder" => Registry::load('strings')->category_order,
    ];



    $form['fields']->role_restricted_category = [
        "title" => Registry::load('strings')->role_restricted_category, "tag" => 'select', "class" => 'field showfieldon'
    ];

    $form['fields']->role_restricted_category["attributes"] = ["fieldclass" => "role_restricted", "checkvalue" => "yes"];

    $form['fields']->role_restricted_category['options'] = [
        "yes" => Registry::load('strings')->yes,
        "no" => Registry::load('strings')->no,
    ];

    $language_id_sr = Registry::load('current_user')->language;

    $join = ["[>]language_strings(string)" => ["site_roles.string_constant" => "string_constant", "AND" => ["language_id" => $language_id_sr]]];
    $columns = ['site_roles.site_role_id', 'string.string_value(name)'];
    $where = ['site_roles.site_role_id[!]' => Registry::load('site_role_attributes')->banned_users];

    $site_roles = DB::connect()->select('site_roles', $join, $columns, $where);

    $site_roles = array_column($site_roles, 'name', 'site_role_id');

    $form['fields']->accessible_roles = [
        "title" => Registry::load('strings')->accessible_roles, "tag" => 'checkbox',
        "class" => 'field role_restricted d-none', 'options' => $site_roles, 'select_all' => true
    ];

    $form['fields']->disabled = [
        "title" => Registry::load('strings')->disabled, "tag" => 'select', "class" => 'field'
    ];
    $form['fields']->disabled['options'] = [
        "yes" => Registry::load('strings')->yes,
        "no" => Registry::load('strings')->no,
    ];


    if (isset($load["group_category_id"])) {

        $cat_info = data_cache(['folder' => 'category_trans/'.$load["group_category_id"], 'filename' => $language_id, 'method' => 'get', 'fs_cache' => true]);

        if (!empty($cat_info)) {
            if (isset($cat_info['category_name']) && !empty($cat_info['category_name'])) {
                $group_category['category_name'] = $cat_info['category_name'];
            }

        }


        $form['fields']->category_name["value"] = $group_category['category_name'];
        $form['fields']->category_order["value"] = $group_category['category_order'];

        $columns = ['site_role_id'];
        $where = ['group_category_id' => $load["group_category_id"]];

        $group_categories_roles = DB::connect()->select('group_categories_roles', $columns, $where);

        $group_categories_roles = array_map(function($item) {
            return (string) $item['site_role_id'];
        }, $group_categories_roles);


        $disabled = $role_restricted_category = 'no';

        if ((int)$group_category['disabled'] === 1) {
            $disabled = 'yes';
        }

        if ((int)$group_category['access_restricted'] === 1) {
            $role_restricted_category = 'yes';
            $form['fields']->accessible_roles["class"] = 'field role_restricted';
        }


        $form['fields']->accessible_roles["values"] = $group_categories_roles;

        $form['fields']->disabled["value"] = $disabled;
        $form['fields']->role_restricted_category["value"] = $role_restricted_category;
    }
}
?>