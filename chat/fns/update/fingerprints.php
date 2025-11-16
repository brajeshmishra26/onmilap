<?php

$noerror = true;
$result = array();
$result['success'] = false;
$result['error_message'] = Registry::load('strings')->something_went_wrong;
$result['error_key'] = 'something_went_wrong';

$user_id = 0;

if (!$force_request) {
    if (isset($data['ban_user_id']) && !role(['permissions' => ['site_users' => 'ban_fingerprint']])) {
        $noerror = false;
    } else if (isset($data['unban_user_id']) && !role(['permissions' => ['site_users' => 'unban_fingerprint']])) {
        $noerror = false;
    }
}

if ($noerror) {

    if (isset($data['ban_user_id'])) {
        $user_id = filter_var($data['ban_user_id'], FILTER_SANITIZE_NUMBER_INT);
    } else if (isset($data['unban_user_id'])) {
        $user_id = filter_var($data['unban_user_id'], FILTER_SANITIZE_NUMBER_INT);
    }
    if (!empty($user_id)) {
        if (!$force_request) {
            $columns = $where = $join = null;

            $columns = ['site_users.site_role_id', 'site_roles.site_role_attribute', 'site_roles.role_hierarchy'];
            $join["[>]site_roles"] = ["site_users.site_role_id" => "site_role_id"];

            $site_user = DB::connect()->select('site_users', $join, $columns, ['user_id' => $user_id]);

            if (isset($site_user[0])) {

                if ($site_user[0]['site_role_attribute'] === 'administrators' || (int)$site_user[0]['site_role_id'] === (int)Registry::load('current_user')->site_role) {
                    $result['error_message'] = Registry::load('strings')->permission_denied;
                    $result['error_key'] = 'permission_denied';
                    $noerror = false;
                    return;
                }

                if (Registry::load('current_user')->site_role_attribute !== 'administrators') {
                    if ((int)$site_user[0]['role_hierarchy'] >= (int)Registry::load('current_user')->role_hierarchy) {
                        $result['error_message'] = Registry::load('strings')->permission_denied;
                        $result['error_key'] = 'permission_denied';
                        $noerror = false;
                        return;
                    }
                }

            }
        }

        if ($noerror) {

            if (isset($data['ban_user_id'])) {
                $fp_disabled = 1;
                DB::connect()->update('site_users_fingerprints', ['fp_disabled' => $fp_disabled, "updated_on" => Registry::load('current_user')->time_stamp], ['user_id' => $user_id]);
                include_once('fns/update/load.php');
                update(['update' => 'firewall', 'ban_user_id' => $user_id, 'return' => true], ["force_request" => true]);
                update(['update' => 'site_user_role', 'ban_user_id' => $user_id, 'return' => true], ["force_request" => true]);

                ws_push(['update' => 'reload_page', 'user_id' => $user_id]);

            } else if (isset($data['unban_user_id'])) {

                $user_fprints = DB::connect()->select('site_users_fingerprints', ['finger_print'], ['user_id' => $user_id]);
                foreach ($user_fprints as $user_fprint) {
                    DB::connect()->update('site_users_fingerprints', ['fp_disabled' => 0, "updated_on" => Registry::load('current_user')->time_stamp], ['finger_print' => $user_fprint['finger_print']]);
                }

                include_once('fns/update/load.php');
                update(['update' => 'firewall', 'unban_user_id' => $user_id, 'return' => true], ["force_request" => true]);
                update(['update' => 'site_user_role', 'unban_user_id' => $user_id, 'return' => true], ["force_request" => true]);

            }



            $result = array();
            $result['success'] = true;
            $result['todo'] = 'reload';
            $result['reload'] = ['site_users', 'online'];

            if (isset($data['info_box'])) {
                $result['info_box']['user_id'] = $user_id;
            }
        }
    }
}