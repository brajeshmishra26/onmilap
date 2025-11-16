<?php

$data["last_realtime_gm"] = filter_var($data["last_realtime_gm"], FILTER_SANITIZE_NUMBER_INT);

if (empty($data["last_realtime_gm"])) {
    $data["last_realtime_gm"] = 0;
}

$columns = $join = $where = null;

$where["group_members.group_id"] = $data["online_group_members"];
$where["group_members.currently_browsing"] = 1;
$where["site_users.online_status"] = 1;

$join['[>]site_users'] = [
    'group_members.user_id' => 'user_id',
];

$online_group_members = DB::connect()->count('group_members', $join, ['group_member_id'], $where);

if ((int)$online_group_members !== (int)$data["last_realtime_gm"]) {
    $result['online_group_members'] = $online_group_members;
    $result['ogm_group_id'] = $data["online_group_members"];

    $escape = true;
}