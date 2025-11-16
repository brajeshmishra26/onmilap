<?php
$site_adverts = array();

if (!role(['permissions' => ['site_adverts' => 'ad_free_account']])) {
    $adverts_columns = [
        'site_advertisements.site_advert_max_height', 'site_advertisements.site_advert_min_height',
        'site_advertisements.site_advert_content', 'site_advertisements.site_advert_placement'
    ];
    $db_join = $db_where = null;
    $adverts_placements = ['chat_page_header', 'chat_page_footer', 'welcome_screen', 'left_content_block', 'info_panel'];

    $db_join["[>]site_advertisements_roles"] = ["site_advertisements.site_advert_id" => "site_advert_id"];

    $db_where = [
        "site_advertisements.site_advert_placement" => $adverts_placements,
        "site_advertisements.disabled" => 0,
        "GROUP" => "site_advertisements.site_advert_id"
    ];


    $db_where["AND #advert_access"]["OR"] = [
        "site_advertisements.site_role_restricted" => 0,
        "site_advertisements_roles.site_role_id" => Registry::load('current_user')->site_role
    ];

    $site_adverts = DB::connect()->rand("site_advertisements", $db_join,
        ['site_advertisements.site_advert_placement' => $adverts_columns],
        $db_where
    );

}

?>