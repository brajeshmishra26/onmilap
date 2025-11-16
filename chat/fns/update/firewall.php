<?php
use Medoo\Medoo;

$noerror = true;
$result = array();
$result['success'] = false;
$result['error_message'] = Registry::load('strings')->something_went_wrong;
$result['error_key'] = 'something_went_wrong';

$blacklist = '';
$user_id = 0;

if (!isset($data['ban_user_id']) && !isset($data['unban_user_id'])) {

    if ($force_request || role(['permissions' => ['super_privileges' => 'firewall']])) {
        $result['error_message'] = Registry::load('strings')->invalid_value;
        $result['error_key'] = 'invalid_value';
        $result['error_variables'] = [];


        if ($noerror) {
            if (isset($data['blacklist']) && !empty($data['blacklist'])) {
                $ip_addresses = $data['blacklist'];

                $ip_addresses = array_filter($ip_addresses, function($ip) {
                    $ip = trim($ip);
                    return filter_var($ip, FILTER_VALIDATE_IP);
                });

                $ip_addresses = array_unique($ip_addresses);

                $insert_data = array();

                foreach ($ip_addresses as $ip_address) {
                    if ($ip_address !== '127.0.0.1') {
                        $insert_data[] = [
                            "ip_address" => $ip_address,
                            "created_on" => Registry::load('current_user')->time_stamp
                        ];
                    }
                }

                if (!empty($insert_data)) {
                    try {
                        DB::connect()->insert("blacklisted_ips", $insert_data);
                    } catch (PDOException $e) {
                        if ($e->getCode() == 23000) {
                            $result['error_message'] = Registry::load('strings')->duplicate_entry_detected;
                            $result['error_key'] = 'duplicate_entry';
                            $noerror = false;
                        }
                    }
                }

            }
        }
    } else {
        $noerror = false;
    }
} else if (!$force_request) {
    if (isset($data['ban_user_id']) && !role(['permissions' => ['site_users' => 'ban_ip_addresses']])) {
        $noerror = false;
    } else if (isset($data['unban_user_id']) && !role(['permissions' => ['site_users' => 'unban_ip_addresses']])) {
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

        $columns = $join = $where = null;
        $sql_statement = '(SELECT <site_role_attribute> FROM <site_roles> WHERE <site_role_id> = <site_users.site_role_id> LIMIT 1)';
        $sql_role_hierarchy = '(SELECT <role_hierarchy> FROM <site_roles> WHERE <site_role_id> = <site_users.site_role_id> LIMIT 1)';
        $columns = [
            'site_users.user_id', 'site_users.site_role_id',
            'site_users_device_logs.ip_address'
        ];
        $columns['site_role_attribute'] = Medoo::raw($sql_statement);
        $columns['role_hierarchy'] = Medoo::raw($sql_role_hierarchy);
        $join["[>]site_users"] = ["site_users_device_logs.user_id" => "user_id"];
        $where["site_users_device_logs.user_id"] = $user_id;
        $user_ip_addresses = DB::connect()->select('site_users_device_logs', $join, $columns, $where);

        $data['blacklist'] = array();

        foreach ($user_ip_addresses as $user_ip_address) {
            $site_user = $user_ip_address;
            $skip_user_id = false;

            if (!$force_request && Registry::load('current_user')->site_role_attribute !== 'administrators') {
                if ((int)$site_user['role_hierarchy'] >= (int)Registry::load('current_user')->role_hierarchy) {
                    $skip_user_id = true;
                    $result['error_message'] = Registry::load('strings')->permission_denied;
                    $result['error_key'] = 'permission_denied';
                    return;
                }
            }

            if ($force_request || $site_user['site_role_attribute'] !== 'administrators' || (int)$site_user['site_role_id'] !== (int)Registry::load('current_user')->site_role) {
                if (!$skip_user_id) {
                    $data['blacklist'][] = $user_ip_address['ip_address'];
                }
            }
        }

        if (isset($data['blacklist']) && !empty($data['blacklist'])) {
            $ip_addresses = $data['blacklist'];
            $ip_addresses = array_unique($ip_addresses);

            if (isset($data['ban_user_id'])) {
                $insert_data = array();

                foreach ($ip_addresses as $ip_address) {
                    if ($ip_address !== '127.0.0.1') {
                        $insert_data[] = [
                            "ip_address" => $ip_address,
                            "created_on" => Registry::load('current_user')->time_stamp
                        ];
                    }
                }

                if (!empty($insert_data)) {
                    DB::connect()->insert("blacklisted_ips", $insert_data);
                }

                if (!$force_request) {
                    ws_push(['update' => 'reload_page', 'user_id' => $user_id]);
                }

            } else if (isset($data['unban_user_id'])) {
                $delete_data = $ip_addresses;

                if (!empty($delete_data)) {
                    DB::connect()->delete("blacklisted_ips", ["ip_address" => $delete_data]);
                }
            }

        }
    }


    $result = array();
    $result['success'] = true;
    $result['todo'] = 'reload';
    $result['reload'] = 'blacklisted_ips';
}

?>