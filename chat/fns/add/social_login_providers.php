<?php

$result = array();
$result['success'] = false;
$result['error_message'] = Registry::load('strings')->something_went_wrong;
$result['error_key'] = 'something_went_wrong';
$result['error_variables'] = [];

if (role(['permissions' => ['social_login_providers' => 'add']])) {

    $result['error_message'] = Registry::load('strings')->invalid_value;
    $result['error_key'] = 'invalid_value';
    $result['error_variables'] = [];

    $noerror = true;

    $additional_credentials = array();
    $disabled = $open_in_popup = $create_user = 0;

    include_once 'fns/data_arrays/social_login_providers.php';

    if (!isset($data['identity_provider']) || empty($data['identity_provider'])) {
        $result['error_variables'][] = ['identity_provider'];
        $noerror = false;
    } else if (!in_array($data['identity_provider'], array_keys($providers))) {
        $result['error_variables'][] = ['identity_provider'];
        $noerror = false;
    }

    if ($noerror) {

        $data['identity_provider'] = htmlspecialchars($data['identity_provider'], ENT_QUOTES, 'UTF-8');
        $data['app_id'] = trim($data['app_id']);
        $data['app_key'] = trim($data['app_key']);
        $data['secret_key'] = trim($data['secret_key']);

        if (isset($data['disabled']) && $data['disabled'] === 'yes') {
            $disabled = 1;
        }

        if (isset($data['create_user']) && $data['create_user'] === 'yes') {
            $create_user = 1;
        }

        if (isset($data['open_in_popup']) && $data['open_in_popup'] === 'yes') {
            $open_in_popup = 1;
        }

        if (isset($data['app_url']) && !empty(trim($data['app_url']))) {
            $additional_credentials['app_url'] = trim($data['app_url']);
        }

        if (isset($data['team_id']) && !empty(trim($data['team_id']))) {
            $additional_credentials['team_id'] = trim($data['team_id']);
        }

        if (isset($data['key_id']) && !empty(trim($data['key_id']))) {
            $additional_credentials['key_id'] = trim($data['key_id']);
        }

        if (isset($data['key_content']) && !empty(trim($data['key_content']))) {
            $additional_credentials['key_content'] = trim($data['key_content']);
        }

        if (isset($data['keycloak_realm']) && !empty(trim($data['keycloak_realm']))) {
            $additional_credentials['keycloak_realm'] = trim($data['keycloak_realm']);
        }

        if (!empty($additional_credentials)) {
            $additional_credentials = json_encode($additional_credentials);
        } else {
            $additional_credentials = null;
        }

        DB::connect()->insert("social_login_providers", [
            "identity_provider" => $data['identity_provider'],
            "app_id" => $data['app_id'],
            "app_key" => $data['app_key'],
            "secret_key" => $data['secret_key'],
            "additional_credentials" => $additional_credentials,
            "open_in_popup" => $open_in_popup,
            "create_user" => $create_user,
            "disabled" => $disabled,
            "updated_on" => Registry::load('current_user')->time_stamp,
        ]);

        if (!DB::connect()->error) {

            $provider_id = DB::connect()->id();

            if (isset($_FILES['icon']['name']) && !empty($_FILES['icon']['name'])) {

                include 'fns/filters/load.php';
                include 'fns/files/load.php';

                if (isImage($_FILES['icon']['tmp_name'])) {
                    $extension = pathinfo($_FILES['icon']['name'])['extension'];
                    $filename = $provider_id.Registry::load('config')->file_seperator.random_string(['length' => 6]).'.'.$extension;

                    if (files('upload', ['upload' => 'icon', 'folder' => 'social_login', 'saveas' => $filename])['result']) {
                        files('resize_img', ['resize' => 'social_login/'.$filename, 'width' => 150, 'height' => 150, 'crop' => true]);
                    }
                }
            }

            $result = array();
            $result['success'] = true;
            $result['todo'] = 'reload';
            $result['reload'] = 'social_login_providers';
        } else {
            $result['error_message'] = Registry::load('strings')->went_wrong;
            $result['error_key'] = 'something_went_wrong';
        }

    }
}

?>