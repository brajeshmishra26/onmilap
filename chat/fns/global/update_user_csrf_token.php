<?php

if (isset(Registry::load('current_user')->login_session_id) && !empty(Registry::load('current_user')->login_session_id)) {

    $total_hours = 6;
    $force_request = false;

    if (!empty(Registry::load('current_user')->csrf_token_generated_on)) {
        $d1 = new DateTime(Registry::load('current_user')->time_stamp);
        $d2 = new DateTime(Registry::load('current_user')->csrf_token_generated_on);
        $interval = $d1->diff($d2);
        $total_hours = ($interval->days * 24) + $interval->h;
    }

    if (!empty($data) && is_array($data)) {
        if (isset($data['force_request']) && $data['force_request']) {
            $force_request = true;
        }
    }

    if ($force_request || $total_hours > 5) {

        Registry::load('current_user')->csrf_token = random_string(['length' => 20]);

        if ($force_request && isset($data['token_code']) && !empty($data['token_code'])) {
            Registry::load('current_user')->csrf_token = $data['token_code'];
        }


        $update_token = [
            'csrf_token' => Registry::load('current_user')->csrf_token,
            'csrf_token_generated_on' => Registry::load('current_user')->time_stamp
        ];
        $where_session = [
            'login_session_id' => Registry::load('current_user')->login_session_id,
            'user_id' => Registry::load('current_user')->id,
        ];

        DB::connect()->update('login_sessions', $update_token, $where_session);

        if (Registry::load('current_user')->login_session_cache) {
            $login_session_id = Registry::load('current_user')->login_session_id;
            $login_session_folder = 'login_sessions/'.date("mY", Registry::load('current_user')->login_session_time_stamp);
            data_cache(['folder' => $login_session_folder, 'filename' => $login_session_id, 'method' => 'append', 'data' => $update_token]);
        }
    }
}
?>