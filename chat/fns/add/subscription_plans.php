<?php

require_once dirname(__DIR__, 3) . '/includes/subscription/helpers.php';

$result = array();
$result['success'] = false;
$result['error_message'] = Registry::load('strings')->went_wrong;
$result['error_key'] = 'something_went_wrong';

if (role(['permissions' => ['super_privileges' => 'core_settings']])) {

    $result['error_message'] = Registry::load('strings')->invalid_value;
    $result['error_key'] = 'invalid_value';
    $result['error_variables'] = [];
    $noerror = true;

    $required_fields = ['plan_name', 'plan_slug', 'price_inr', 'price_usd', 'total_minutes', 'daily_minutes_limit', 'validity_days'];

    foreach ($required_fields as $field) {
        if (!isset($data[$field]) || trim((string)$data[$field]) === '') {
            $result['error_variables'][] = [$field];
            $noerror = false;
        }
    }

    if ($noerror) {
        $plan_name = htmlspecialchars(trim($data['plan_name']), ENT_QUOTES, 'UTF-8');
        $plan_slug = strtolower(trim($data['plan_slug']));
        $plan_slug = preg_replace('/[^a-z0-9-]+/i', '-', $plan_slug);
        $plan_slug = preg_replace('/-+/', '-', $plan_slug);
        $plan_slug = trim($plan_slug, '-');

        $planTable = subscription_medoo_table('plans');

        if (empty($plan_slug)) {
            $result['error_variables'][] = ['plan_slug'];
            $noerror = false;
        } else if (DB::connect()->has($planTable, ['slug' => $plan_slug])) {
            $result['error_message'] = Registry::load('strings')->duplicate_entry_detected;
            $result['error_key'] = 'duplicate_entry';
            $result['error_variables'][] = ['plan_slug'];
            $noerror = false;
        }

        $price_inr = (float)$data['price_inr'];
        $price_usd = (float)$data['price_usd'];
        $total_minutes = filter_var($data['total_minutes'], FILTER_SANITIZE_NUMBER_INT);
        $daily_minutes_limit = filter_var($data['daily_minutes_limit'], FILTER_SANITIZE_NUMBER_INT);
        $validity_days = filter_var($data['validity_days'], FILTER_SANITIZE_NUMBER_INT);
        $extends_validity_days = isset($data['extends_validity_days']) ? filter_var($data['extends_validity_days'], FILTER_SANITIZE_NUMBER_INT) : 0;

        if ($price_inr < 0) {
            $result['error_variables'][] = ['price_inr'];
            $noerror = false;
        }
        if ($price_usd < 0) {
            $result['error_variables'][] = ['price_usd'];
            $noerror = false;
        }
        if (empty($total_minutes) || $total_minutes <= 0) {
            $result['error_variables'][] = ['total_minutes'];
            $noerror = false;
        }
        if (empty($daily_minutes_limit) || $daily_minutes_limit <= 0) {
            $result['error_variables'][] = ['daily_minutes_limit'];
            $noerror = false;
        }
        if (empty($validity_days) || $validity_days <= 0) {
            $result['error_variables'][] = ['validity_days'];
            $noerror = false;
        }
        if ($extends_validity_days === false || $extends_validity_days < 0) {
            $extends_validity_days = 0;
        }
    }

    if ($noerror) {
        $is_top_up = (isset($data['is_top_up']) && $data['is_top_up'] === 'yes') ? 1 : 0;
        $is_active = (isset($data['is_active']) && $data['is_active'] === 'no') ? 0 : 1;

        $planTable = subscription_medoo_table('plans');

        DB::connect()->insert($planTable, [
            'slug' => $plan_slug,
            'name' => $plan_name,
            'price_usd' => $price_usd,
            'price_inr' => $price_inr,
            'total_minutes' => (int)$total_minutes,
            'daily_minutes_limit' => (int)$daily_minutes_limit,
            'validity_days' => (int)$validity_days,
            'extends_validity_days' => (int)$extends_validity_days,
            'is_top_up' => $is_top_up,
            'is_active' => $is_active,
        ]);

        if (!DB::connect()->error) {
            $result = array();
            $result['success'] = true;
            $result['todo'] = 'refresh';
        } else {
            $result['error_message'] = Registry::load('strings')->went_wrong;
            $result['error_key'] = 'something_went_wrong';
        }
    }
}

?>
