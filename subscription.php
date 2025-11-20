<?php
$originalCwd = getcwd();
$chatPath = __DIR__.'/chat';
chdir($chatPath);

if (!function_exists('build_page_base_url')) {
    function build_page_base_url(): string
    {
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $dir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
        if ($dir === '/' || $dir === '.') {
            $dir = '';
        }
        $dir = trim($dir, '/');
        return rtrim($scheme.'://'.$host.(!empty($dir) ? '/'.$dir : ''), '/').'/';
    }
}

require_once __DIR__.'/api/bootstrap.php';

if (!function_exists('ensure_default_subscription_plans')) {
    function ensure_default_subscription_plans(): void
    {
        if (!class_exists('DB')) {
            return;
        }

        try {
            $db = DB::connect();
        } catch (\Throwable $exception) {
            return;
        }

        $planTable = subscription_medoo_table('plans');

        try {
            $activePlans = (int)$db->count($planTable, ['is_active' => 1]);
        } catch (\Throwable $exception) {
            return;
        }

        if ($activePlans > 0) {
            return;
        }

        $defaultPlans = [
            [
                'slug' => 'starter',
                'name' => 'Starter',
                'price_usd' => 0,
                'price_inr' => 0,
                'total_minutes' => 300,
                'daily_minutes_limit' => 60,
                'validity_days' => 30,
                'extends_validity_days' => 0,
                'is_top_up' => 0,
                'is_active' => 1,
            ],
            [
                'slug' => 'professional',
                'name' => 'Professional',
                'price_usd' => 59,
                'price_inr' => 4900,
                'total_minutes' => 1800,
                'daily_minutes_limit' => 120,
                'validity_days' => 30,
                'extends_validity_days' => 0,
                'is_top_up' => 0,
                'is_active' => 1,
            ],
            [
                'slug' => 'creator',
                'name' => 'Creator',
                'price_usd' => 95,
                'price_inr' => 7900,
                'total_minutes' => 3000,
                'daily_minutes_limit' => 180,
                'validity_days' => 30,
                'extends_validity_days' => 0,
                'is_top_up' => 0,
                'is_active' => 1,
            ],
        ];

        foreach ($defaultPlans as $plan) {
            try {
                $db->insert($planTable, $plan);
            } catch (\Throwable $exception) {
                break;
            }
        }
    }
}

ensure_default_subscription_plans();

$allowedViews = ['recharge', 'active', 'history'];
$requestedView = isset($_GET['view']) ? strtolower(trim((string)$_GET['view'])) : 'recharge';
if (!in_array($requestedView, $allowedViews, true)) {
    $requestedView = 'recharge';
}

if (
    class_exists('Registry')
    && method_exists('Registry', 'stored')
    && Registry::stored('config')
) {
    $subscriptionConfig = Registry::load('config');
    if (!isset($subscriptionConfig->current_page) || empty($subscriptionConfig->current_page)) {
        $subscriptionConfig->current_page = 'subscription';
    }

    $subscriptionConfig->site_url = build_page_base_url().'chat/';
}

$isLoggedIn = Registry::load('current_user')->logged_in ?? false;

if (!$isLoggedIn) {
    $redirectUrl = Registry::load('config')->site_url.'subscription.php';
    if (!empty($_SERVER['QUERY_STRING'])) {
        $redirectUrl .= '?'.$_SERVER['QUERY_STRING'];
    }

    $loginUrl = Registry::load('config')->site_url.Registry::load('config')->authentication_page_url_path.'/';
    $loginUrl .= '?redirect='.urlencode($redirectUrl);

    chdir($originalCwd);
    header('Location: '.$loginUrl);
    exit;
}

Registry::load('appearance')->display_chat_alone = true;

Registry::load('config')->show_subscription_page = false;
Registry::load('config')->show_active_plan_page = false;
Registry::load('config')->show_plan_history_page = false;

switch ($requestedView) {
    case 'active':
        Registry::load('config')->show_active_plan_page = true;
        break;
    case 'history':
        Registry::load('config')->show_plan_history_page = true;
        break;
    default:
        Registry::load('config')->show_subscription_page = true;
}

if ($requestedView === 'recharge' && isset($_GET['plan'])) {
    $selectedPlan = trim((string)$_GET['plan']);
    if ($selectedPlan !== '') {
        Registry::load('config')->subscription_selected_plan = $selectedPlan;
    }
}

include 'layouts/chat_page/layout.php';
chdir($originalCwd);
