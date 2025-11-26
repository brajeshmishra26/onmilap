<?php

require_once dirname(__DIR__, 3).'/includes/subscription/helpers.php';

$result = array();
$result['success'] = false;
$result['error_message'] = Registry::load('strings')->went_wrong;
$result['error_key'] = 'something_went_wrong';

if (role(['permissions' => ['super_privileges' => 'core_settings']])) {
    $plan_ids = array();

    if (isset($data['plan_id'])) {
        if (is_array($data['plan_id'])) {
            $plan_ids = array_map('intval', array_filter($data['plan_id'], 'ctype_digit'));
        } else {
            $plan_id = filter_var($data['plan_id'], FILTER_SANITIZE_NUMBER_INT);
            if (!empty($plan_id)) {
                $plan_ids[] = (int)$plan_id;
            }
        }
    }

    if (!empty($plan_ids)) {
        $planTable = subscription_medoo_table('plans');
        DB::connect()->delete($planTable, ['id' => $plan_ids]);

        if (!DB::connect()->error) {
            $result = array();
            $result['success'] = true;
            $result['todo'] = 'reload';
            $result['reload'] = 'subscription_plans';
        }
    }
}

?>
