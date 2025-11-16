<?php
$result = array();
$noerror = true;

$result['success'] = false;
$result['error_message'] = Registry::load('strings')->went_wrong;
$result['error_key'] = 'something_went_wrong';
$blacklist_ip_ids = array();

if (role(['permissions' => ['super_privileges' => 'firewall']])) {

    if (isset($data['blacklist_ip_id'])) {
        if (!is_array($data['blacklist_ip_id'])) {
            $data["blacklist_ip_id"] = filter_var($data["blacklist_ip_id"], FILTER_SANITIZE_NUMBER_INT);
            $blacklist_ip_ids[] = $data["blacklist_ip_id"];
        } else {
            $blacklist_ip_ids = array_filter($data["blacklist_ip_id"], 'ctype_digit');
        }
    }

    if (isset($data['blacklist_ip_id']) && !empty($data['blacklist_ip_id'])) {

        DB::connect()->delete("blacklisted_ips", ["blacklist_ip_id" => $blacklist_ip_ids]);

        if (!DB::connect()->error) {
            $result = array();
            $result['success'] = true;
            $result['todo'] = 'reload';
            $result['reload'] = 'blacklisted_ips';
        } else {
            $result['errormsg'] = Registry::load('strings')->went_wrong;
        }
    }
}
?>