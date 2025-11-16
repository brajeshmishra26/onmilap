<?php

include 'fns/filters/load.php';

$result = array();
$result['success'] = false;
$result['error_message'] = Registry::load('strings')->went_wrong;
$result['error_key'] = 'something_went_wrong';
$noerror = true;

if (role(['permissions' => ['super_privileges' => 'message_scheduler']])) {


    $result['error_message'] = Registry::load('strings')->invalid_value;
    $result['error_key'] = 'invalid_value';
    $message_datas = array();

    if (isset($data['create_method']) && $data['create_method'] === 'import') {
        if (!isset($_FILES['csv_file']['name']) || empty($_FILES['csv_file']['name'])) {
            $result['error_variables'][] = ['csv_file'];
            $noerror = false;
        }

        if ($noerror) {
            if (isset($_FILES['csv_file']['name']) && !empty($_FILES['csv_file']['name'])) {
                $filename = 'import_bulk_messages_'.strtotime("now").'.csv';

                include 'fns/files/load.php';

                $upload_info = [
                    'upload' => 'csv_file',
                    'folder' => 'assets/cache/',
                    'saveas' => $filename,
                    'real_path' => true,
                ];

                $csv_file = files('upload', $upload_info);

                if ($csv_file['result']) {
                    $csv_file_location = 'assets/cache/'.$filename;

                    if (file_exists($csv_file_location)) {
                        if (($handle = fopen($csv_file_location, "r")) !== FALSE) {

                            $delimiter = detect_csv_delimiter($csv_file_location);

                            while (($msg_data = fgetcsv($handle, 1500, $delimiter)) !== FALSE) {
                                if (isset($msg_data[0]) && $msg_data[0] === "Group ID") {
                                    continue;
                                }

                                $message_datas[] = [
                                    'group_id' => isset($msg_data[0]) ? $msg_data[0] : null,
                                    'sender' => isset($msg_data[1]) ? $msg_data[1] : null,
                                    'send_message_on' => isset($msg_data[2]) ? $msg_data[2] : null,
                                    'message' => isset($msg_data[3]) ? $msg_data[3] : null,
                                ];
                            }
                            fclose($handle);
                        }

                        unlink($csv_file_location);
                    }
                }
            }

        }
    } else {
        $message_datas = array();
        $message_datas[] = $data;
    }

    $msg_inserted = false;

    if (!empty($message_datas)) {

        include('fns/HTMLPurifier/load.php');
        $allowed_tags = 'p,span[class],b,em,i,u,strong,s,';
        $allowed_tags .= 'a[href],ol,ul,li,br';

        $config = HTMLPurifier_Config::createDefault();
        $config->set('HTML.Allowed', $allowed_tags);
        $config->set('Attr.AllowedClasses', array());
        $config->set('HTML.Nofollow', true);
        $config->set('HTML.TargetBlank', true);
        $config->set('AutoFormat.RemoveEmpty', true);

        $define = $config->getHTMLDefinition(true);
        $define->addAttribute('span', 'class', new CustomClassDef(array('emoji_icon'), array('emoji-')));

        $purifier = new HTMLPurifier($config);

        foreach ($message_datas as $data) {
            $required_fields = ['sender', 'group_id', 'message', 'send_message_on'];


            if (isset($data['group_id'])) {
                $data["group_id"] = filter_var($data["group_id"], FILTER_SANITIZE_NUMBER_INT);
            }

            foreach ($required_fields as $required_field) {
                if (!isset($data[$required_field]) || empty(trim($data[$required_field]))) {
                    $result['error_variables'][] = [$required_field];
                    $noerror = false;
                }
            }


            $data['sender'] = sanitize_username($data['sender'], false);

            if (!empty($data['sender'])) {
                $sender = DB::connect()->select('site_users', ['site_users.user_id'], ["LIMIT" => 1, "site_users.username" => $data['sender']]);
            }

            if (empty($data['sender']) || !isset($sender[0])) {
                $result['error_message'] = Registry::load('strings')->account_not_found;
                $result['error_key'] = 'account_not_found';
                $noerror = false;
            } else {
                $sender_id = $sender[0]['user_id'];
            }


            if (isset($data['send_message_on']) && !empty($data['send_message_on'])) {

                $input_datetime = new DateTime($data['send_message_on'], new DateTimeZone(Registry::load('current_user')->time_zone));
                $output_timezone = new DateTimeZone('Asia/Kolkata');
                $input_datetime->setTimezone($output_timezone);
                $data['send_message_on'] = $input_datetime->format('Y-m-d H:i:s');

                if (empty($data['send_message_on'])) {
                    $noerror = false;
                }
            }

            if ($noerror) {

                $message = $purifier->purify(trim($data['message']));

                if (!empty($message)) {

                    $repeat_message = 0;

                    if (isset($data["repeat_message"]) && $data["repeat_message"] == 'yes') {
                        $repeat_message = 1;
                    }

                    if (!isset($data["repetition_rate"])) {
                        $data["repetition_rate"] = 0;
                    }

                    if (!isset($data["repeat_interval"])) {
                        $data["repeat_interval"] = 0;
                    }


                    DB::connect()->insert("scheduled_messages", [
                        "message_content" => $message,
                        "group_id" => $data["group_id"],
                        "user_id" => $sender_id,
                        "repeat_message" => $repeat_message,
                        "repetition_rate" => $data["repetition_rate"],
                        "repeat_interval" => $data["repeat_interval"],
                        "send_message_on" => $data["send_message_on"],
                        "created_on" => Registry::load('current_user')->time_stamp,
                        "updated_on" => Registry::load('current_user')->time_stamp,
                    ]);

                    $msg_inserted = true;
                }
            }
        }

        if ($msg_inserted) {
            $result = array();
            $result['success'] = true;
            $result['todo'] = 'reload';
            $result['reload'] = 'scheduled_messages';
        } else if ($noerror) {
            $result['error_message'] = Registry::load('strings')->invalid_value;
            $result['error_key'] = 'invalid_value';
        }
    }
}

?>