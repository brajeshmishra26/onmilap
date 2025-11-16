<?php

$output = array();
$index = 1;
$language_id = Registry::load('current_user')->language;

if (Registry::load('settings')->memberships === 'enable') {
    if (role(['permissions' => ['memberships' => 'view_membership_info']])) {
        $columns = $join = $where = null;
        $columns = [
            'site_users_membership.membership_package_id', 'site_users_membership.expiring_on',
            'site_users_membership.started_on', 'site_users_membership.non_expiring',
            "membership_packages.cancellable", "membership_packages.refundable_on_cancel"
        ];
        $where['site_users_membership.user_id'] = Registry::load('current_user')->id;
        $join = ["[>]membership_packages" => ["site_users_membership.membership_package_id" => "membership_package_id"]];

        $user_membership = DB::connect()->select('site_users_membership', $join, $columns, $where);

        if (isset($user_membership[0]) && !empty($user_membership[0]['membership_package_id'])) {
            $user_membership = $user_membership[0];
            $package_name = 'membership_package_'.$user_membership['membership_package_id'];

            $output['info_items'][$index] = new stdClass();
            $output['info_items'][$index]->title = Registry::load('strings')->package_name.": ";
            $output['info_items'][$index]->value = Registry::load('strings')->$package_name;
            $index++;

            $started_on['date'] = $user_membership['started_on'];
            $started_on['auto_format'] = true;
            $started_on['include_time'] = true;

            $output['info_items'][$index] = new stdClass();
            $output['info_items'][$index]->title = Registry::load('strings')->started_on.': ';
            $output['info_items'][$index]->value = get_date($started_on)['date'];
            $index++;

            $expiring_on['date'] = $user_membership['expiring_on'];
            $expiring_on['auto_format'] = true;
            $expiring_on['include_time'] = true;

            $output['info_items'][$index] = new stdClass();
            $output['info_items'][$index]->title = Registry::load('strings')->expiring_on.': ';

            $timestamp = strtotime($user_membership['expiring_on']);
            $current_timestamp = time();

            if ($timestamp < $current_timestamp) {
                $output['info_items'][$index]->title = Registry::load('strings')->expired_on.': ';
            }

            if (!empty($user_membership['non_expiring'])) {
                $output['info_items'][$index]->value = Registry::load('strings')->lifetime;
            } else {
                $output['info_items'][$index]->value = get_date($expiring_on)['date'];
            }

            $index++;


            if (!empty($user_membership['cancellable'])) {

                $output['info_items'][$index] = new stdClass();
                $output['info_items'][$index]->title = Registry::load('strings')->cancellable.': ';
                $output['info_items'][$index]->value = Registry::load('strings')->yes;
                $index++;

                $refundable_on_cancel = 'no';
                if (!empty($user_membership['refundable_on_cancel'])) {
                    $refundable_on_cancel = 'yes';
                }
                $output['info_items'][$index] = new stdClass();
                $output['info_items'][$index]->title = Registry::load('strings')->refundable.': ';
                $output['info_items'][$index]->value = Registry::load('strings')->$refundable_on_cancel;
                $index++;

                $output['info_items'][$index] = new stdClass();
                $output['info_items'][$index]->title = Registry::load('strings')->subscription.': ';
                $output['info_items'][$index]->button = Registry::load('strings')->cancel;

                $output['info_items'][$index]->attributes['class'] = 'ask_confirmation';
                $output['info_items'][$index]->attributes['column'] = 'second';
                $output['info_items'][$index]->attributes['data-info_box'] = true;
                $output['info_items'][$index]->attributes['data-remove'] = 'site_user_membership_order';
                $output['info_items'][$index]->attributes['confirmation'] = Registry::load('strings')->confirm_action;
                $output['info_items'][$index]->attributes['submit_button'] = Registry::load('strings')->yes;
                $output['info_items'][$index]->attributes['cancel_button'] = Registry::load('strings')->no;
                $index++;
            }
        }
    }


    $columns = $join = $where = null;
    $columns = [
        'membership_packages.membership_package_id', 'membership_packages.string_constant', 'membership_packages.is_recurring',
        'membership_packages.pricing', 'membership_packages.duration', 'membership_packages.duration_in_minutes'
    ];

    $join["[>]membership_packages_roles"] = ["membership_packages.membership_package_id" => "membership_package_id"];

    $where["AND #pack_access"]["OR"] = [
        "membership_packages.role_restricted_package" => 0,
        "membership_packages_roles.site_role_id" => Registry::load('current_user')->site_role
    ];

    $where["membership_packages.disabled[!]"] = 1;
    $where["ORDER"] = ["membership_packages.package_sort_index" => "ASC", "membership_packages.membership_package_id" => "DESC"];

    $packages = DB::connect()->select('membership_packages', $join, $columns, $where);

    $index = 1;

    foreach ($packages as $package) {
        $string_constant = $package['string_constant'];
        $output['packages'][$index] = new stdClass();
        $output['packages'][$index]->membership_package_id = $package['membership_package_id'];
        $output['packages'][$index]->title = Registry::load('strings')->$string_constant;

        if (Registry::load('settings')->currency_symbol_placement === 'right') {
            $output['packages'][$index]->pricing = $package['pricing'].Registry::load('settings')->default_currency_symbol;
        } else {
            $output['packages'][$index]->pricing = Registry::load('settings')->default_currency_symbol.$package['pricing'];
        }

        if (role(['permissions' => ['memberships' => 'enroll_membership']])) {
            $output['packages'][$index]->purchase_button = Registry::load('strings')->select_plan;
        }

        if (!empty($package["is_recurring"])) {
            $output['packages'][$index]->duration = Registry::load('strings')->duration.': '.Registry::load('strings')->lifetime;
        } else {
            $output['packages'][$index]->duration = Registry::load('strings')->duration.': ';

            if (!empty($package['duration'])) {
                $output['packages'][$index]->duration .= $package['duration'].' '.Registry::load('strings')->days;
            }

            if (!empty($package["duration_in_minutes"])) {
                $output['packages'][$index]->duration .= ' '.$package["duration_in_minutes"].' '.Registry::load('strings')->minutes;
            }
        }

        $benefits = data_cache(['folder' => 'membership_package_benefits', 'filename' => $package["membership_package_id"], 'method' => 'get', 'fs_cache' => true]);

        if (!empty($benefits)) {
            $language_index = 'language_'.$language_id;
            if (isset($benefits[$language_index])) {
                $output['packages'][$index]->benefits = $benefits[$language_index];
            }
        }


        $index++;
    }
}
?>