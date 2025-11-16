<?php

include 'fns/filters/load.php';
include 'fns/files/load.php';
include_once 'fns/cloud_storage/load.php';

$result = array();
$noerror = true;

$result['success'] = false;
$result['error_message'] = Registry::load('strings')->went_wrong;
$result['error_key'] = 'something_went_wrong';
$user_id = Registry::load('current_user')->id;

if (role(['permissions' => ['storage' => 'delete_files']])) {

    if (isset($data['user_id'])) {
        if (role(['permissions' => ['storage' => 'super_privileges']])) {
            if (!is_array($data['user_id'])) {
                $data["user_id"] = filter_var($data["user_id"], FILTER_SANITIZE_NUMBER_INT);
                $user_id = array();
                $user_id[] = $data["user_id"];
            } else {
                $user_id = array_filter($data["user_id"], 'ctype_digit');
            }
        }
    }

    if (!empty($user_id)) {

        $columns = $join = $where = null;
        $site_user_id = array();

        $columns = [
            'site_users.user_id', 'site_roles.role_hierarchy'
        ];
        $join["[>]site_roles"] = ["site_users.site_role_id" => "site_role_id"];
        $where["site_users.user_id"] = $user_id;

        $site_users = DB::connect()->select('site_users', $join, $columns, $where);

        foreach ($site_users as $site_user) {
            $skip_user_id = false;

            if ((int)$site_user['user_id'] !== (int)Registry::load('current_user')->id) {
                if (Registry::load('current_user')->site_role_attribute !== 'administrators') {
                    if ((int)$site_user['role_hierarchy'] > (int)Registry::load('current_user')->role_hierarchy) {
                        $skip_user_id = true;
                        $result['error_message'] = Registry::load('strings')->permission_denied;
                        $result['error_key'] = 'permission_denied';
                    }
                }
            }
            if (!$skip_user_id) {
                $site_user_id[] = $site_user['user_id'];
            }

        }

        $user_id = $site_user_id;

        if (isset($data['file'])) {

            if (is_array($user_id)) {
                $user_id = $user_id[0];
            }

            if (is_array($data['file'])) {
                $files = $data['file'];
            } else {
                $files = array();
                $files[] = $data['file'];
            }

            foreach ($files as $file) {
                $file_name = sanitize_filename($file);

                if (!empty($file_name)) {
                    $file = 'assets/files/storage/'.$user_id.'/files/'.$file_name;
                    files('delete', ['delete' => $file, 'real_path' => true]);

                    if (Registry::load('settings')->cloud_storage !== 'disable') {
                        cloud_storage_module(['delete_file' => $file]);
                    }

                    $extension = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

                    $videoExtensions = ['mp4', 'avi', 'mov', 'wmv', 'mkv', 'flv', 'webm', '3gp', 'mpeg', 'mpg'];

                    if (in_array($extension, $videoExtensions)) {
                        $file = 'assets/files/storage/'.$user_id.'/thumbnails/'.pathinfo($file_name, PATHINFO_FILENAME).'.jpg';
                    } else {
                        $file = 'assets/files/storage/'.$user_id.'/thumbnails/'.$file_name;
                    }


                    files('delete', ['delete' => $file, 'real_path' => true]);

                    if (Registry::load('settings')->cloud_storage !== 'disable') {
                        cloud_storage_module(['delete_file' => $file]);
                    }

                }
            }
        } else if (isset($data['delete_all']) && $data['delete_all']) {

            $user_ids = array();

            if (!is_array($user_id)) {
                $user_ids[] = $user_id;
            } else {
                $user_ids = $user_id;
            }

            foreach ($user_ids as $user_id) {

                $file = 'assets/files/storage/'.$user_id.'/';
                files('delete', ['delete' => $file, 'real_path' => true]);

                if (Registry::load('settings')->cloud_storage !== 'disable') {
                    cloud_storage_module(['delete_folder' => $file]);
                    $user_filePath = 'assets/cache/user_storage/'.$user_id.'.cache';

                    if (file_exists($user_filePath)) {
                        unlink($user_filePath);
                    }
                }
            }
        }

        $result = array();
        $result['success'] = true;
        $result['todo'] = 'reload';
        $result['reload'] = ['site_user_files', 'storage'];
    }
}
?>