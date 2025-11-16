<?php

if (Registry::load('current_user')->login_session_cache || $force_remove) {

    if (!empty($user_id)) {
        $columns = $join = $where = null;
        $columns = [
            'login_sessions.login_session_id', 'login_sessions.time_stamp',
        ];
        $where = [
            "login_sessions.status" => 1,
            'LIMIT' => 50
        ];


        $where["login_sessions.user_id"] = $user_id;

        $user_login_sessions = DB::connect()->select('login_sessions', $columns, $where);

        foreach ($user_login_sessions as $user_login_session) {
            $login_session_id = $user_login_session['login_session_id'];
            $login_session_folder = 'login_sessions/'.date("mY", $user_login_session['time_stamp']);
            data_cache(['folder' => $login_session_folder, 'filename' => $login_session_id, 'method' => 'delete']);
        }
    } else {
        include_once('fns/files/load.php');
        $delete_logs = [
            'delete' => 'assets/cache/files_cache/login_sessions/',
            'real_path' => true,
        ];
        files('delete', $delete_logs);
    }

}
?>