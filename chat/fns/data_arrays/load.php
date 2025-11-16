<?php

function data_array($data) {
    $result = array();
    $force_request = $api_request = false;


    if (isset($data["load"])) {
        $data["load"] = preg_replace("/[^a-zA-Z0-9_]+/", "", $data["load"]);
        $data["load"] = str_replace("sfn_", "", $data["load"]);
    }

    if (isset($data["load"]) && !empty($data["load"])) {
        $loadfnfile = 'fns/data_arrays/'.$data["load"].'.php';
        if (file_exists($loadfnfile)) {
            include($loadfnfile);
        }
    }


    return $result;
}