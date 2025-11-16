<?php

if (role(['permissions' => ['site_adverts' => 'create']])) {

    include 'fns/filters/load.php';
    $result = array();
    $noerror = true;
    $disabled = $site_role_restricted = $group_id = 0;
    $result['success'] = false;
    $result['error_message'] = Registry::load('strings')->invalid_value;
    $result['error_key'] = 'invalid_value';
    $result['error_variables'] = [];

    $advert_placements = [
        'left_content_block', 'entry_page_form_header',
        'entry_page_form_footer', 'info_panel', 'welcome_screen',
        'landing_page_groups_section', 'landing_page_faq_section',
        'chat_page_footer', 'chat_page_header'
    ];

    if (!isset($data['advert_name']) || empty(trim($data['advert_name']))) {
        $result['error_variables'][] = ['advert_name'];
        $noerror = false;
    }

    if (!isset($data['advert_max_height']) || empty(trim($data['advert_max_height']))) {
        $result['error_variables'][] = ['advert_max_height'];
        $noerror = false;
    }

    if (!isset($data['advert_placement']) || empty(trim($data['advert_placement'])) || !in_array($data['advert_placement'], $advert_placements)) {
        $result['error_variables'][] = ['advert_placement'];
        $noerror = false;
    }

    if ($noerror) {
        $data['advert_name'] = htmlspecialchars($data['advert_name'], ENT_QUOTES, 'UTF-8');
        $data['advert_min_height'] = filter_var($data['advert_min_height'], FILTER_SANITIZE_NUMBER_INT);
        $data['advert_max_height'] = filter_var($data['advert_max_height'], FILTER_SANITIZE_NUMBER_INT);

        if (isset($data['disabled']) && $data['disabled'] === 'yes') {
            $disabled = 1;
        }

        if (isset($data['site_role_restricted']) && $data['site_role_restricted'] === 'yes') {
            $site_role_restricted = 1;
        }

        DB::connect()->insert("site_advertisements", [
            "site_advert_name" => $data['advert_name'],
            "site_advert_min_height" => $data['advert_min_height'],
            "site_advert_max_height" => $data['advert_max_height'],
            "site_advert_placement" => $data['advert_placement'],
            "site_advert_content" => $data['advert_content'],
            "site_role_restricted" => $site_role_restricted,
            "disabled" => $disabled,
            "updated_on" => Registry::load('current_user')->time_stamp,
        ]);

        if (!DB::connect()->error) {

            $advert_id = DB::connect()->id();
            
            DB::connect()->delete('site_advertisements_roles', ['site_advert_id' => $advert_id]);

            if ((int)$site_role_restricted === 1) {
                if (isset($data['site_roles'])) {
                    if (is_array($data['site_roles']) && !empty($data['site_roles'])) {

                        $site_roles = $data['site_roles'];
                        $insert_roles_data = array();

                        foreach ($site_roles as $site_role) {
                            $insert_roles_data[] = [
                                'site_advert_id' => $advert_id,
                                'site_role_id' => $site_role
                            ];
                        }

                        if (!empty($insert_roles_data)) {
                            DB::connect()->insert("site_advertisements_roles", $insert_roles_data);
                        }
                    }
                }
            }

            $result = array();
            $result['success'] = true;
            $result['todo'] = 'reload';
            $result['reload'] = 'site_adverts';
        } else {
            $result['error_message'] = Registry::load('strings')->went_wrong;
            $result['error_key'] = 'something_went_wrong';
        }

    }
}
?>