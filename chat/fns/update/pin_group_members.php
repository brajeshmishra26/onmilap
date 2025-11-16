<?php

include 'fns/filters/load.php';

$result = array();
$result['success'] = false;
$result['error_message'] = Registry::load('strings')->something_went_wrong;
$result['error_key'] = 'something_went_wrong';
$ban_till = null;
$super_privileges = false;

if ($force_request || role(['permissions' => ['groups' => 'super_privileges']])) {
    $super_privileges = true;
}


if ($force_request) {
    if (isset($data['user'])) {
        $columns = $join = $where = null;

        $columns = ['site_users.user_id'];
        $where["OR"] = ["site_users.username" => $data['user'], "site_users.email_address" => $data['user']];
        $where["LIMIT"] = 1;

        $site_user = DB::connect()->select('site_users', $columns, $where);

        if (isset($site_user[0])) {
            $data["user_id"] = $site_user[0]['user_id'];
        } else {
            $result = array();
            $result['success'] = false;
            $result['error_message'] = Registry::load('strings')->account_not_found;
            $result['error_key'] = 'account_not_found';
            $result['error_variables'] = [];
            return;
        }
    }

    if (isset($data['group'])) {
        $columns = $join = $where = null;

        $columns = ['groups.group_id'];
        $where["OR"] = ["groups.group_id" => $data['group'], "groups.slug" => $data['group']];
        $where["LIMIT"] = 1;

        $find_group = DB::connect()->select('groups', $columns, $where);

        if (isset($find_group[0])) {
            $data['group_id'] = $find_group[0]['group_id'];
        } else {
            $result = array();
            $result['success'] = false;
            $result['error_message'] = 'Group Not Found';
            $result['error_key'] = 'group_not_found';
            $result['error_variables'] = [];
            return;
        }
    }
    if (isset($data['group_role'])) {
        $columns = $join = $where = null;
        $columns = ['group_roles.group_role_id'];
        $where["group_roles.group_role_id"] = $data['group_role'];

        $where["LIMIT"] = 1;

        $group_role_id = DB::connect()->select('group_roles', $columns, $where);

        if (isset($group_role_id[0])) {
            $data['group_role_id'] = $group_role_id[0]['group_role_id'];
        }
    }
}

if (isset($data['group_id'])) {
    $data["group_id"] = filter_var($data["group_id"], FILTER_SANITIZE_NUMBER_INT);

    if (!empty($data['group_id'])) {
        $columns = $where = $join = null;
        $columns = [
            'groups.group_id', 'group_members.pin_group_member',
            'group_roles.group_role_attribute', 'group_roles.role_hierarchy'
        ];

        $join["[>]group_members"] = ["groups.group_id" => "group_id", "AND" => ["user_id" => Registry::load('current_user')->id]];
        $join["[>]group_roles"] = ["group_members.group_role_id" => "group_role_id"];

        $where['groups.group_id'] = $data['group_id'];

        $group_info = DB::connect()->select('groups', $join, $columns, $where);

        if (isset($group_info[0])) {
            $group_info = $group_info[0];
        } else {
            return false;
        }

        if (!$super_privileges && !role(['permissions' => ['group_members' => 'pin_group_members'], 'group_role_id' => $group_info['group_role_id']])) {
            return false;
        }

        if ($super_privileges || isset($group_info['pin_group_member'])) {


            if (!isset($data['user_order']) || empty($data["user_order"])) {
                $result['error_message'] = Registry::load('strings')->invalid_value;
                $result['error_key'] = 'invalid_value';
                $result['error_variables'] = ['user_order'];
            }

            if (isset($data['user_id']) && isset($data["user_order"])) {
                $data["user_id"] = filter_var($data["user_id"], FILTER_SANITIZE_NUMBER_INT);
                $data["user_order"] = filter_var($data["user_order"], FILTER_SANITIZE_NUMBER_INT);

                if (!empty($data['user_id'])) {

                    $pin_group_member = 0;

                    if (isset($data['pin_user']) && !empty($data['pin_user'])) {

                        if ($data['pin_user'] === 'yes') {
                            $pin_group_member = 1;

                            if (!empty($data['user_order'])) {
                                $pin_group_member = $data['user_order'];
                            }
                        } 
                    }


                    $columns = $join = $where = null;
                    $where['AND'] = ['group_members.group_id' => $data['group_id'], 'group_members.user_id' => $data['user_id']];

                    DB::connect()->update("group_members", [
                        "pin_group_member" => $pin_group_member,
                        "updated_on" => Registry::load('current_user')->time_stamp,
                    ], $where);


                    $result = array();
                    $result['success'] = true;
                    $result['todo'] = 'reload';
                    $result['reload'] = 'group_members';

                    if (isset($data['info_box'])) {
                        $result['info_box']['user_id'] = $data['user_id'];
                        $result['info_box']['group_identifier'] = $data['group_id'];
                    }
                }
            }
        }
    }
}