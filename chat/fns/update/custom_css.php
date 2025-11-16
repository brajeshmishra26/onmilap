<?php

$noerror = true;
$result = array();
$result['success'] = false;
$result['error_message'] = Registry::load('strings')->something_went_wrong;
$result['error_key'] = 'something_went_wrong';

if (role(['permissions' => ['super_privileges' => 'customizer']])) {

    $styleTagPattern = "/<style[\s\S]*?>/i";

    $content = '';

    if (isset($data['global_css']) && !empty($data['global_css'])) {
        $content = $data['global_css'];
    }

    if (preg_match($styleTagPattern, $content)) {
        $result = array();
        $result['success'] = false;
        $result['error_message'] = Registry::load('strings')->not_pure_css_code;
        $result['error_key'] = 'something_went_wrong';
        return;
    } else {
        $update = fopen("assets/css/common/custom_css.css", "w");
        fwrite($update, $content);
        fclose($update);
    }

    $content = '';

    if (isset($data['custom_css_chat_page']) && !empty($data['custom_css_chat_page'])) {
        $content = $data['custom_css_chat_page'];
    }

    if (preg_match($styleTagPattern, $content)) {
        $result = array();
        $result['success'] = false;
        $result['error_message'] = Registry::load('strings')->not_pure_css_code;
        $result['error_key'] = 'something_went_wrong';
        return;
    } else {
        $update = fopen("assets/css/chat_page/custom_css.css", "w");
        fwrite($update, $content);
        fclose($update);
    }

    $content = '';

    if (isset($data['custom_css_entry_page']) && !empty($data['custom_css_entry_page'])) {
        $content = $data['custom_css_entry_page'];
    }

    if (preg_match($styleTagPattern, $content)) {
        $result = array();
        $result['success'] = false;
        $result['error_message'] = Registry::load('strings')->not_pure_css_code;
        $result['error_key'] = 'something_went_wrong';
        return;
    } else {
        $update = fopen("assets/css/entry_page/custom_css.css", "w");
        fwrite($update, $content);
        fclose($update);
    }

    $content = '';

    if (isset($data['custom_css_landing_page']) && !empty($data['custom_css_landing_page'])) {
        $content = $data['custom_css_landing_page'];
    }

    if (preg_match($styleTagPattern, $content)) {
        $result = array();
        $result['success'] = false;
        $result['error_message'] = Registry::load('strings')->not_pure_css_code;
        $result['error_key'] = 'something_went_wrong';
        return;
    } else {
        $update = fopen("assets/css/landing_page/custom_css.css", "w");
        fwrite($update, $content);
        fclose($update);
    }



    cache(['rebuild' => 'settings']);
    cache(['rebuild' => 'css']);

    $result = array();
    $result['success'] = true;
    $result['todo'] = 'refresh';
    $result['on_refresh'] = [
        'attributes' => [
            'class' => 'load_form',
            'form' => 'custom_css',
        ]
    ];
}
?>