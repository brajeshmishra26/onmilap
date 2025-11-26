<?php

require_once dirname(__DIR__, 3).'/includes/subscription/helpers.php';

$form = array();

if (role(['permissions' => ['super_privileges' => 'core_settings']])) {
    $strings_store = Registry::load('strings');
    $label = static function (string $key, string $fallback) use ($strings_store): string {
        return (isset($strings_store->$key) && !empty($strings_store->$key)) ? $strings_store->$key : $fallback;
    };

    $form['loaded'] = new stdClass();
    $form['fields'] = new stdClass();

    $todo = 'add';
    $plan = null;
    $plan_id = 0;

    if (isset($load['plan_id'])) {
        $plan_id = filter_var($load['plan_id'], FILTER_SANITIZE_NUMBER_INT);
    }

    if (!empty($plan_id)) {
        $planTable = subscription_medoo_table('plans');
        $plan = DB::connect()->get($planTable, '*', ['id' => $plan_id]);

        if (!$plan) {
            return false;
        }

        $todo = 'update';
        $form['loaded']->title = $label('edit_plan', 'Edit Plan');
        $form['loaded']->button = $label('update', 'Update');

        $form['fields']->plan_id = [
            'tag' => 'input', 'type' => 'hidden', 'class' => 'd-none', 'value' => $plan_id,
        ];
    } else {
        $form['loaded']->title = $label('add_plan', 'Add Plan');
        $form['loaded']->button = $label('add', 'Add');
    }

    $value = static function (?array $plan, string $key, $default) {
        if ($plan !== null && array_key_exists($key, $plan)) {
            return $plan[$key];
        }

        return $default;
    };

    $form['fields']->process = [
        'tag' => 'input', 'type' => 'hidden', 'class' => 'd-none', 'value' => $todo,
    ];

    $form['fields']->$todo = [
        'tag' => 'input', 'type' => 'hidden', 'class' => 'd-none', 'value' => 'subscription_plans',
    ];

    $form['fields']->plan_name = [
        'title' => $label('plan_name', 'Plan Name'), 'tag' => 'input', 'type' => 'text', 'class' => 'field',
        'placeholder' => $label('plan_name', 'Plan Name'),
        'value' => $value($plan, 'name', ''),
    ];

    $form['fields']->plan_slug = [
        'title' => $label('plan_slug', 'Plan Slug'), 'tag' => 'input', 'type' => 'text', 'class' => 'field',
        'placeholder' => $label('plan_slug', 'Plan Slug'),
        'value' => $value($plan, 'slug', ''),
    ];

    $form['fields']->price_inr = [
        'title' => $label('price_inr', 'Price (INR)'), 'tag' => 'input', 'type' => 'number', 'class' => 'field',
        'attributes' => ['step' => '0.01', 'min' => '0'],
        'value' => (string)$value($plan, 'price_inr', '0'),
    ];

    $form['fields']->price_usd = [
        'title' => $label('price_usd', 'Price (USD)'), 'tag' => 'input', 'type' => 'number', 'class' => 'field',
        'attributes' => ['step' => '0.01', 'min' => '0'],
        'value' => (string)$value($plan, 'price_usd', '0'),
    ];

    $form['fields']->total_minutes = [
        'title' => $label('total_minutes', 'Total Minutes'), 'tag' => 'input', 'type' => 'number', 'class' => 'field',
        'attributes' => ['min' => '1'],
        'value' => (string)$value($plan, 'total_minutes', '60'),
    ];

    $form['fields']->daily_minutes_limit = [
        'title' => $label('daily_minutes_limit', 'Daily Minutes Limit'), 'tag' => 'input', 'type' => 'number', 'class' => 'field',
        'attributes' => ['min' => '1'],
        'value' => (string)$value($plan, 'daily_minutes_limit', '60'),
    ];

    $form['fields']->validity_days = [
        'title' => $label('validity_days', 'Validity (Days)'), 'tag' => 'input', 'type' => 'number', 'class' => 'field',
        'attributes' => ['min' => '1'],
        'value' => (string)$value($plan, 'validity_days', '30'),
    ];

    $form['fields']->extends_validity_days = [
        'title' => $label('extends_validity_days', 'Extends Validity (Days)'), 'tag' => 'input', 'type' => 'number', 'class' => 'field',
        'attributes' => ['min' => '0'],
        'value' => (string)$value($plan, 'extends_validity_days', '0'),
    ];

    $form['fields']->is_top_up = [
        'title' => $label('is_top_up', 'Is Top-Up Plan?'), 'tag' => 'select', 'class' => 'field',
    ];
    $form['fields']->is_top_up['options'] = [
        'no' => $label('no', 'No'),
        'yes' => $label('yes', 'Yes'),
    ];
    $form['fields']->is_top_up['value'] = ((int)$value($plan, 'is_top_up', 0) === 1) ? 'yes' : 'no';

    $form['fields']->is_active = [
        'title' => $label('is_active', 'Active?'), 'tag' => 'select', 'class' => 'field',
    ];
    $form['fields']->is_active['options'] = [
        'yes' => $label('yes', 'Yes'),
        'no' => $label('no', 'No'),
    ];
    $form['fields']->is_active['value'] = ((int)$value($plan, 'is_active', 1) === 1) ? 'yes' : 'no';
}

?>
