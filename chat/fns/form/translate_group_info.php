<?php

$group_id = 0;
$super_privileges = false;
$language_id = Registry::load('current_user')->language;


if (role(['permissions' => ['groups' => 'super_privileges']])) {
    $super_privileges = true;
}


if (isset($load['group_id'])) {
    $load["group_id"] = filter_var($load["group_id"], FILTER_SANITIZE_NUMBER_INT);
    if (!empty($load['group_id'])) {
        $todo = 'update';
        $group_id = $load["group_id"];
    }
}

$form['loaded'] = new stdClass();
$form['fields'] = new stdClass();

if (!empty($group_id)) {

    if (isset($load["language_id"])) {
        $load["language_id"] = filter_var($load["language_id"], FILTER_SANITIZE_NUMBER_INT);

        if (!empty($load["language_id"])) {
            $language_id = $load["language_id"];
        }
    }

    $columns = $where = $join = null;
    $columns = [
        'languages.name', 'languages.language_id'
    ];

    $where["languages.language_id[!]"] = null;

    $languages = DB::connect()->select('languages', $columns, $where);


    $columns = $where = $join = null;
    $columns = [
        'groups.group_id', 'groups.name', 'groups.description',
    ];

    $join["[>]group_members"] = ["groups.group_id" => "group_id", "AND" => ["user_id" => Registry::load('current_user')->id]];

    $where["groups.group_id"] = $group_id;
    $where["LIMIT"] = 1;

    $group = DB::connect()->select('groups', $join, $columns, $where);

    if (!isset($group[0])) {
        return false;
    } else {
        $group = $group[0];
    }

    if (!$super_privileges && isset($group['suspended']) && !empty($group['suspended'])) {
        return false;
    }

    if ($super_privileges || isset($group['group_role_id']) && !empty($group['group_role_id'])) {
        if (!$super_privileges && !role(['permissions' => ['group' => 'translate_group_info'], 'group_role_id' => $group['group_role_id']])) {
            return false;
        }
    } else {
        return false;
    }



    $form['fields']->group_id = [
        "tag" => 'input', "type" => 'hidden', "class" => 'd-none', "value" => $group_id
    ];

    $form['loaded']->title = Registry::load('strings')->edit_group;
    $form['loaded']->button = Registry::load('strings')->update;


    $form['fields']->update = [
        "tag" => 'input', "type" => 'hidden', "class" => 'd-none', "value" => "translate_group_info"
    ];

    $form['fields']->language_id = [
        "title" => Registry::load('strings')->language, "tag" => 'select', "class" => 'field',
    ];

    $form['fields']->language_id["class"] = 'field switch_form';

    if (isset($load["language_id"]) && !empty($load["language_id"])) {
        $form['fields']->language_id['value'] = $language_id;
    }

    $form['fields']->language_id["parent_attributes"] = [
        "form" => "translate_group_info",
        "data-group_id" => $group_id,
    ];

    foreach ($languages as $language) {
        $language_identifier = $language['language_id'];
        $form['fields']->language_id['options'][$language_identifier] = $language['name'];
    }

    $form['fields']->group_name = [
        "title" => Registry::load('strings')->group_name, "tag" => 'input', "type" => "text",
        "class" => 'field', "placeholder" => Registry::load('strings')->group_name,
    ];


    $form['fields']->description = [
        "title" => Registry::load('strings')->description, "tag" => 'textarea', "class" => 'field',
        "placeholder" => Registry::load('strings')->description,
    ];

    $form['fields']->description["attributes"] = ["rows" => 4];

    $group_info = data_cache(['folder' => 'group_trans/'.$group_id, 'filename' => $language_id, 'method' => 'get', 'fs_cache' => true]);

    if (!empty($group_info)) {
        if (isset($group_info['name']) && !empty($group_info['name'])) {
            $group['name'] = $group_info['name'];
        }

        if (isset($group_info['description']) && !empty($group_info['description'])) {
            $group['description'] = $group_info['description'];
        }
    }


    $form['fields']->group_name["value"] = $group['name'];

    if (!empty($group['description'])) {
        $form['fields']->description["value"] = $group['description'];
    }
}
?>