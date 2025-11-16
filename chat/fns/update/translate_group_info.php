<?php

$result = array();
$result['success'] = false;
$result['error_message'] = Registry::load('strings')->went_wrong;
$result['error_key'] = 'something_went_wrong';

$group_id = 0;
$super_privileges = false;
$language_id = Registry::load('current_user')->language;


if (role(['permissions' => ['groups' => 'super_privileges']])) {
    $super_privileges = true;
}

if (isset($data['group_id'])) {
    $data["group_id"] = filter_var($data["group_id"], FILTER_SANITIZE_NUMBER_INT);
    if (!empty($data['group_id'])) {
        $todo = 'update';
        $group_id = $data["group_id"];
    }
}

if (!empty($group_id)) {

    if (isset($data["language_id"])) {
        $data["language_id"] = filter_var($data["language_id"], FILTER_SANITIZE_NUMBER_INT);

        if (!empty($data["language_id"])) {
            $language_id = $data["language_id"];
        }
    }

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

    $group_info = array();

    if (isset($data['group_name'])) {
        $data['group_name'] = htmlspecialchars(trim($data['group_name']), ENT_QUOTES, 'UTF-8');
        if (!empty($data['group_name'])) {
            $group_info["name"] = $data['group_name'];
        }
    }

    if (isset($data['description'])) {
        $data['description'] = htmlspecialchars(trim($data['description']), ENT_QUOTES, 'UTF-8');
        $group_info["description"] = $data['description'];
    }


    data_cache(['folder' => 'group_trans/'.$group_id, 'data' => $group_info, 'filename' => $language_id, 'method' => 'set', 'fs_cache' => true]);

    $result = array();
    $result['success'] = true;
    $result['todo'] = 'reload';
    $result['reload'] = 'groups';

}