<?php
$check_fingerprint = true;

if (isset(Registry::load('current_user')->log_device) && empty(Registry::load('current_user')->log_device)) {
    if (isset(Registry::load('current_user')->login_from_user_id) && !empty(Registry::load('current_user')->login_from_user_id)) {
        $check_fingerprint = false;
    }
}

if (Registry::load('current_user')->site_role_attribute === 'administrators') {
    $check_fingerprint = false;
}

if ($check_fingerprint) {

    $add_fingerprint = false;
    $columns = $where = null;
    $columns = [
        'site_users_fingerprints.user_id', 'fp_disabled'
    ];

    $where["OR"]["AND #first_query"] = [
        "site_users_fingerprints.finger_print" => $data["userFpToken"],
        "site_users_fingerprints.user_id" => $current_user_id,
    ];
    $where["OR"]["AND #second_query"] = [
        "site_users_fingerprints.finger_print" => $data["userFpToken"],
        "site_users_fingerprints.fp_disabled" => 1,
    ];
    $fingerprints = DB::connect()->select('site_users_fingerprints', $columns, $where);

    if (empty($fingerprints)) {
        $add_fingerprint = true;
    } else {
        foreach ($fingerprints as $fingerprint) {
            if ((int)$fingerprint['fp_disabled'] === 1) {
                include_once('fns/update/load.php');
                update(['update' => 'firewall', 'ban_user_id' => $current_user_id, 'return' => true], ["force_request" => true]);
                update(['update' => 'site_user_role', 'ban_user_id' => $current_user_id, 'return' => true], ["force_request" => true]);
                $result['reload_page'] = true;
                $escape = true;
                break;
            } else if ((int)$fingerprint['user_id'] !== $current_user_id) {
                $add_fingerprint = true;
            }
        }
    }

    if ($add_fingerprint) {
        $insert_fp = [
            "finger_print" => $data['userFpToken'],
            "user_id" => $current_user_id,
            "created_on" => Registry::load('current_user')->time_stamp,
            "updated_on" => Registry::load('current_user')->time_stamp,
        ];
        DB::connect()->insert('site_users_fingerprints', $insert_fp);
    }
}
?>