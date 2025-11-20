<?php

use App\Subscription\ExchangeRateService;
use App\Subscription\PlanModel;
use App\Subscription\SubscriptionModel;
use App\Subscription\SubscriptionService;
use Medoo\Medoo;

require_once __DIR__ . '/helpers.php';

require_once __DIR__ . '/PlanModel.php';
require_once __DIR__ . '/SubscriptionModel.php';
require_once __DIR__ . '/ExchangeRateService.php';
require_once __DIR__ . '/SubscriptionService.php';
require_once __DIR__ . '/../../chat/fns/payments/razorpay/autoload.php';
require_once __DIR__ . '/../../chat/fns/payments/omnipay/autoload.php';

if (!function_exists('subscription_service')) {
    function subscription_service(): SubscriptionService
    {
        static $service = null;

        if ($service === null) {
            $db = DB::connect();
            $planModel = new PlanModel($db);
            $subscriptionModel = new SubscriptionModel($db);

            $exchange = null;
            $apiKey = Registry::load('config')->EXCHANGE_RATE_API_KEY ?? null;
            if (!empty($apiKey)) {
                $cacheDir = __DIR__ . '/../../cache';
                $exchange = new ExchangeRateService($apiKey, $cacheDir);
            }

            $service = new SubscriptionService($db, $planModel, $subscriptionModel, $exchange);
        }

        return $service;
    }
}
