<?php
$sub_process = null;
$redirect = null;

$page_content = [
    'title' => 'Exporting User Data',
    'loading_text' => 'Exporting User Data',
    'subtitle' => 'Please Wait',
    'redirect' => Registry::load('config')->site_url.'basic_process?process=export_contacts'
];

$columns = ['user_id', 'email_address', 'display_name', 'phone_number'];

$where = [
    'em_subscription' => 0,
    'LIMIT' => 50
];

$where["site_users.site_role_id[!]"] = Registry::load('site_role_attributes')->guest_users;

$users = DB::connect()->select('site_users', $columns, $where);


if (!empty($users)) {

    $contacts = array();

    foreach ($users as $user) {
        $email_domain = substr(strrchr($user['email_address'], "@"), 1);

        if (substr($email_domain, -10) !== '.guestuser') {
            $contacts[] = [
                'email_address' => $user['email_address'],
                'display_name' => $user['display_name'],
                'phone_number' => $user['phone_number'],
                'user_id' => $user['user_id']
            ];
        }
    }


    if (!empty($contacts)) {
        include_once('fns/email_marketing/load.php');
        $output = email_marketing_module(["import" => $contacts]);
    }

    if (empty($contacts) || $output['success']) {
        $lastElement = end($users);
        $lastUserId = $lastElement['user_id'];

        if (!empty($lastUserId)) {
            DB::connect()->update('site_users', ['em_subscription' => 1], ['site_users.user_id[<=]' => $lastUserId]);
        }
    } else {
        $page_content = [
            'title' => 'Exporting Failed',
            'page_content' => $output['error_message'],
            'heading' => 'Error!',
            'page_status' => 'error',
            'button_text' => 'Go to Homepage',
            'button_link' => Registry::load('config')->site_url
        ];
    }
} else {
    $page_content = [
        'title' => 'Successfully Completed',
        'page_content' => 'Process Successfully Completed',
        'heading' => 'Yay!',
        'page_status' => 'success',
        'button_text' => 'Go to Homepage',
        'button_link' => Registry::load('config')->site_url
    ];
}

?>