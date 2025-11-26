<?php

require_once dirname(__DIR__, 3).'/includes/subscription/helpers.php';

if (role(['permissions' => ['super_privileges' => 'core_settings']])) {
    $strings = Registry::load('strings');
    $db = DB::connect();
    $planTable = subscription_medoo_table('plans');

    $columns = [
        'id', 'name', 'slug', 'price_inr', 'price_usd', 'total_minutes', 'daily_minutes_limit',
        'validity_days', 'extends_validity_days', 'is_top_up', 'is_active'
    ];

    $where = [];

    if (!empty($data['offset'])) {
        $data['offset'] = array_map('intval', explode(',', $data['offset']));
        $where['id[!]'] = $data['offset'];
    }

    if (!empty($data['search'])) {
        $where['OR'] = [
            'name[~]' => $data['search'],
            'slug[~]' => $data['search'],
        ];
    }

    $recordsPerCall = Registry::load('settings')->records_per_call ?? 25;
    $where['LIMIT'] = $recordsPerCall;

    if (isset($data['sortby'])) {
        switch ($data['sortby']) {
            case 'name_asc':
                $where['ORDER'] = ['name' => 'ASC'];
                break;
            case 'name_desc':
                $where['ORDER'] = ['name' => 'DESC'];
                break;
            case 'price_asc':
                $where['ORDER'] = ['price_usd' => 'ASC'];
                break;
            case 'price_desc':
                $where['ORDER'] = ['price_usd' => 'DESC'];
                break;
            default:
                $where['ORDER'] = ['id' => 'DESC'];
                break;
        }
    } else {
        $where['ORDER'] = ['id' => 'DESC'];
    }

    $plans = $db->select($planTable, $columns, $where);

    $output = [];
    $output['loaded'] = new stdClass();
    $output['loaded']->title = isset($strings->subscription_plans) && !empty($strings->subscription_plans)
        ? $strings->subscription_plans
        : 'Subscription Plans';
    $output['loaded']->loaded = 'subscription_plans';
    $output['loaded']->offset = !empty($data['offset']) ? $data['offset'] : [];

    if (role(['permissions' => ['super_privileges' => 'core_settings']])) {
        $output['todo'] = new stdClass();
        $output['todo']->class = 'load_form';
        $output['todo']->title = isset($strings->add_plan) && !empty($strings->add_plan) ? $strings->add_plan : 'Add Plan';
        $output['todo']->attributes['form'] = 'subscription_plans';
        $output['todo']->attributes['enlarge'] = true;
    }

    if (role(['permissions' => ['super_privileges' => 'core_settings']])) {
        $output['multiple_select'] = new stdClass();
        $output['multiple_select']->title = isset($strings->delete) ? $strings->delete : 'Delete';
        $output['multiple_select']->attributes['class'] = 'ask_confirmation';
        $output['multiple_select']->attributes['data-remove'] = 'subscription_plans';
        $output['multiple_select']->attributes['multi_select'] = 'plan_id';
        $output['multiple_select']->attributes['submit_button'] = isset($strings->yes) ? $strings->yes : 'Yes';
        $output['multiple_select']->attributes['cancel_button'] = isset($strings->no) ? $strings->no : 'No';
        $output['multiple_select']->attributes['confirmation'] = isset($strings->confirm_action)
            ? $strings->confirm_action
            : 'Are you sure?';
    }

    $output['sortby'][1] = new stdClass();
    $output['sortby'][1]->sortby = isset($strings->sort_by_default) ? $strings->sort_by_default : 'Default';
    $output['sortby'][1]->class = 'load_aside';
    $output['sortby'][1]->attributes['load'] = 'subscription_plans';

    $output['sortby'][2] = new stdClass();
    $output['sortby'][2]->sortby = isset($strings->name) ? $strings->name : 'Name';
    $output['sortby'][2]->class = 'load_aside sort_asc';
    $output['sortby'][2]->attributes['load'] = 'subscription_plans';
    $output['sortby'][2]->attributes['sort'] = 'name_asc';

    $output['sortby'][3] = new stdClass();
    $output['sortby'][3]->sortby = isset($strings->name) ? $strings->name : 'Name';
    $output['sortby'][3]->class = 'load_aside sort_desc';
    $output['sortby'][3]->attributes['load'] = 'subscription_plans';
    $output['sortby'][3]->attributes['sort'] = 'name_desc';

    $output['sortby'][4] = new stdClass();
    $output['sortby'][4]->sortby = 'Price';
    $output['sortby'][4]->class = 'load_aside sort_asc';
    $output['sortby'][4]->attributes['load'] = 'subscription_plans';
    $output['sortby'][4]->attributes['sort'] = 'price_asc';

    $output['sortby'][5] = new stdClass();
    $output['sortby'][5]->sortby = 'Price';
    $output['sortby'][5]->class = 'load_aside sort_desc';
    $output['sortby'][5]->attributes['load'] = 'subscription_plans';
    $output['sortby'][5]->attributes['sort'] = 'price_desc';

    $imagePlaceholder = Registry::load('config')->site_url.'assets/files/defaults/orders.png';

    $i = 1;
    foreach ($plans as $plan) {
        $output['loaded']->offset[] = $plan['id'];

        $output['content'][$i] = new stdClass();
        $output['content'][$i]->title = $plan['name'];
        $output['content'][$i]->identifier = $plan['id'];
        $output['content'][$i]->class = 'subscription_plans';
        $output['content'][$i]->image = $imagePlaceholder;

        $status = ((int)$plan['is_active'] === 1)
            ? (isset($strings->active) ? $strings->active : 'Active')
            : (isset($strings->inactive) ? $strings->inactive : 'Inactive');
        $planType = ((int)$plan['is_top_up'] === 1) ? 'Top-up' : 'Base';
        $priceParts = [];
        if ($plan['price_inr'] !== null && $plan['price_inr'] !== '') {
            $priceParts[] = '₹'.number_format((float)$plan['price_inr'], 2);
        }
        if ($plan['price_usd'] !== null && $plan['price_usd'] !== '') {
            $priceParts[] = '$'.number_format((float)$plan['price_usd'], 2);
        }
        $prices = !empty($priceParts) ? implode(' / ', $priceParts) : '—';

        $minutes = (int)$plan['total_minutes'].' min';
        if (!empty($plan['daily_minutes_limit'])) {
            $minutes .= ' • '.$plan['daily_minutes_limit'].' daily';
        }

        $validity = (int)$plan['validity_days'].' day';
        if ((int)$plan['validity_days'] !== 1) {
            $validity .= 's';
        }

        $output['content'][$i]->subtitle = implode(' | ', array_filter([
            'Slug: '.$plan['slug'],
            $status.' '.$planType,
            $prices,
            $minutes,
            $validity,
        ]));

        $output['options'][$i][1] = new stdClass();
        $output['options'][$i][1]->option = isset($strings->edit) ? $strings->edit : 'Edit';
        $output['options'][$i][1]->class = 'load_form';
        $output['options'][$i][1]->attributes['form'] = 'subscription_plans';
        $output['options'][$i][1]->attributes['enlarge'] = true;
        $output['options'][$i][1]->attributes['data-plan_id'] = $plan['id'];

        $output['options'][$i][2] = new stdClass();
        $output['options'][$i][2]->option = isset($strings->delete) ? $strings->delete : 'Delete';
        $output['options'][$i][2]->class = 'ask_confirmation';
        $output['options'][$i][2]->attributes['data-remove'] = 'subscription_plans';
        $output['options'][$i][2]->attributes['data-plan_id'] = $plan['id'];
        $output['options'][$i][2]->attributes['submit_button'] = isset($strings->yes) ? $strings->yes : 'Yes';
        $output['options'][$i][2]->attributes['cancel_button'] = isset($strings->no) ? $strings->no : 'No';
        $output['options'][$i][2]->attributes['confirmation'] = isset($strings->confirm_action)
            ? $strings->confirm_action
            : 'Are you sure?';

        $i++;
    }
}
?>
