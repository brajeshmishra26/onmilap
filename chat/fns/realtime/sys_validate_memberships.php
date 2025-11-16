<?php

$oneDaysFrontTimestamp = new DateTime();
$oneDaysFrontTimestamp->modify('+1 days');
$oneDaysFrontTimestamp = $oneDaysFrontTimestamp->format('Y-m-d H:i:s');

$columns = $join = $where = null;
$columns = [
    'site_users_membership.membership_package_id', 'site_users_membership.expiring_on', 'site_users_membership.membership_info_id',
    'site_users_membership.started_on', 'site_users_membership.non_expiring', 'site_users_membership.user_id',
    'membership_packages.site_role_id_on_expire', 'membership_packages.related_site_role_id', 'site_users_membership.notified_expiry',
    'membership_packages.pricing', 'site_users_membership.package_amount', 'site_users_membership.currency_code'
];

$join = ["[>]membership_packages" => ["site_users_membership.membership_package_id" => "membership_package_id"]];

$where = [
    'site_users_membership.non_expiring[!]' => 1,
    'LIMIT' => 10
];

$where["OR"]["AND #first_query"] = [
    'site_users_membership.expiring_on[<]' => $oneDaysFrontTimestamp,
    "site_users_membership.notified_expiry" => 0,
];
$where["OR"]["AND #second_query"] = [
    'site_users_membership.expiring_on[<]' => Registry::load('current_user')->time_stamp,
    "site_users_membership.membership_expired" => 0,
];

$memberships = DB::connect()->select('site_users_membership', $join, $columns, $where);

foreach ($memberships as $membership) {
    if (empty($membership['non_expiring'])) {
        $timestamp = strtotime($membership['expiring_on']);
        $current_timestamp = time();

        if ($timestamp < $current_timestamp) {
            $renewal_failed = true;

            include_once 'fns/add/load.php';
            $renewal_status = [
                'add' => 'site_user_membership_order',
                'membership_package_id' => $membership['membership_package_id'],
                'return' => true
            ];

            $private_data = ['force_request' => true, 'set_user_id' => $membership['user_id']];
            
            $membership['package_amount'] = (float)$membership['package_amount'];

            if (!empty($membership['package_amount'])) {
                $private_data['package_amount'] = $membership['package_amount'];
            }

            if (!empty($membership['currency_code'])) {
                $private_data['currency_code'] = $membership['currency_code'];
            }

            $renewal_status = add($renewal_status, $private_data);

            if (isset($renewal_status['success']) && $renewal_status['success']) {
                $renewal_failed = false;
            }

            if ($renewal_failed) {
                $site_role_id_on_expire = $membership['site_role_id_on_expire'];

                if (empty($site_role_id_on_expire)) {
                    $site_role_id_on_expire = Registry::load('site_role_attributes')->default_site_role;
                }

                DB::connect()->update('site_users', ['site_role_id' => $site_role_id_on_expire],
                    ['site_users.user_id' => $membership['user_id']]);

                remove_login_sesion_cache($membership['user_id']);

                DB::connect()->insert("site_notifications", [
                    "user_id" => $membership['user_id'],
                    "notification_type" => 'membership_expired',
                    "related_user_id" => $membership['user_id'],
                    "created_on" => Registry::load('current_user')->time_stamp,
                    "updated_on" => Registry::load('current_user')->time_stamp,
                ]);
                DB::connect()->update("site_users_membership", ["membership_expired" => 1, "notified_expiry" => 1], ["membership_info_id" => $membership['membership_info_id']]);
            }

        } else if (empty($membership['notified_expiry'])) {

            if ($timestamp - $current_timestamp < 21 * 3600) {

                DB::connect()->insert("site_notifications", [
                    "user_id" => $membership['user_id'],
                    "notification_type" => 'membership_package_expiring_soon',
                    "related_user_id" => $membership['user_id'],
                    "created_on" => Registry::load('current_user')->time_stamp,
                    "updated_on" => Registry::load('current_user')->time_stamp,
                ]);

                DB::connect()->update("site_users_membership", ["notified_expiry" => 1], ["membership_info_id" => $membership['membership_info_id']]);

            }
        }
    }
}


?>