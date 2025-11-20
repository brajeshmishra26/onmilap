<?php
declare(strict_types=1);

if (!class_exists('MailerSpy')) {
    class MailerSpy
    {
        public static array $payloads = [];

        public static function reset(): void
        {
            self::$payloads = [];
        }
    }
}

if (!function_exists('mailer')) {
    function mailer($todo, $data)
    {
        MailerSpy::$payloads[] = ['todo' => $todo, 'data' => $data];
        return ['success' => true];
    }
}

require_once __DIR__ . '/../../chat/fns/registry/load.php';
require_once __DIR__ . '/../../chat/fns/sql/Medoo.php';
require_once __DIR__ . '/../../includes/subscription/PlanModel.php';
require_once __DIR__ . '/../../includes/subscription/SubscriptionModel.php';
require_once __DIR__ . '/../../includes/subscription/SubscriptionService.php';

use App\Subscription\PlanModel;
use App\Subscription\SubscriptionModel;
use App\Subscription\SubscriptionService;
use Medoo\Medoo;

final class SubscriptionServiceTestSuite
{
    public function run(): void
    {
        $this->testPurchasePlanCreatesSubscription();
        $this->testDeductMinutesQueuesEmailAndUpdatesBalances();
        fwrite(STDOUT, "SubscriptionService tests passed.\n");
    }

    private function testPurchasePlanCreatesSubscription(): void
    {
        [$service, $db] = $this->createService();
        $result = $service->purchasePlan(1, 'starter', 'USD');

        $this->assertArrayHasKey('subscription_id', $result, 'Purchase response should contain subscription_id');
        $subscriptions = $db->select('gr_user_subscriptions', '*');
        $this->assertCount(1, $subscriptions, 'One subscription should be stored');
        $this->assertSame('active', $subscriptions[0]['status'], 'Subscription must be active');
        $this->assertSame(300, (int) $subscriptions[0]['total_remaining_minutes'], 'Allocated minutes should match the plan');
    }

    private function testDeductMinutesQueuesEmailAndUpdatesBalances(): void
    {
        [$service] = $this->createService();
        $service->purchasePlan(1, 'starter', 'USD');
        MailerSpy::reset();

        $remaining = $service->deductMinutesOnSessionEnd(1, 12.5, ['session_id' => 99]);

        $this->assertSame(287, $remaining['daily_remaining_minutes'], 'Daily minutes should decrement by rounded usage');
        $this->assertSame(287, $remaining['total_remaining_minutes'], 'Total minutes should decrement as well');
        $this->assertCount(1, MailerSpy::$payloads, 'Session summary email should be queued exactly once');
        $this->assertSame('send', MailerSpy::$payloads[0]['todo']);
        $this->assertSame('Recharge now', MailerSpy::$payloads[0]['data']['button']['label']);
    }

    private function createService(): array
    {
        \Registry::add('config', (object) ['site_url' => 'https://example.test/']);
        \Registry::add('settings', (object) ['system_email_address' => 'alerts@example.test']);

        $db = new Medoo([
            'database_type' => 'sqlite',
            'database_file' => ':memory:',
            'database_name' => 'memory'
        ]);

        $this->bootstrapSchema($db);
        $this->seedUser($db);
        $this->seedPlan($db);

        $planModel = new PlanModel($db);
        $subscriptionModel = new SubscriptionModel($db);
        $service = new SubscriptionService($db, $planModel, $subscriptionModel);

        return [$service, $db];
    }

    private function bootstrapSchema(Medoo $db): void
    {
        $pdo = $db->pdo;
        $pdo->exec('DROP TABLE IF EXISTS gr_plans');
        $pdo->exec('CREATE TABLE gr_plans (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            slug TEXT UNIQUE,
            name TEXT,
            price_usd REAL,
            price_inr REAL,
            total_minutes INTEGER,
            daily_minutes_limit INTEGER,
            validity_days INTEGER,
            extends_validity_days INTEGER DEFAULT 0,
            is_top_up INTEGER DEFAULT 0,
            is_active INTEGER DEFAULT 1
        )');

        $pdo->exec('DROP TABLE IF EXISTS gr_user_subscriptions');
        $pdo->exec('CREATE TABLE gr_user_subscriptions (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER,
            plan_id INTEGER,
            status TEXT,
            start_at TEXT,
            end_at TEXT,
            timezone TEXT,
            total_allocated_minutes INTEGER,
            total_remaining_minutes INTEGER,
            daily_minutes_limit INTEGER,
            daily_remaining_minutes INTEGER,
            last_reset_at TEXT NULL,
            last_activity_at TEXT NULL,
            currency TEXT,
            payment_reference TEXT,
            next_recharge_at TEXT,
            concurrency_token INTEGER DEFAULT 0
        )');

        $pdo->exec('DROP TABLE IF EXISTS gr_topups');
        $pdo->exec('CREATE TABLE gr_topups (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            subscription_id INTEGER,
            user_id INTEGER,
            plan_id INTEGER,
            minutes_purchased INTEGER,
            validity_days INTEGER,
            extends_validity_days INTEGER,
            amount REAL,
            currency TEXT,
            payment_gateway TEXT,
            payment_reference TEXT,
            status TEXT,
            metadata TEXT
        )');

        $pdo->exec('DROP TABLE IF EXISTS gr_subscription_events');
        $pdo->exec('CREATE TABLE gr_subscription_events (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            subscription_id INTEGER,
            user_id INTEGER,
            event_type TEXT,
            details TEXT
        )');

        $pdo->exec('DROP TABLE IF EXISTS site_users');
        $pdo->exec('CREATE TABLE site_users (
            user_id INTEGER PRIMARY KEY,
            email TEXT,
            display_name TEXT
        )');
    }

    private function seedPlan(Medoo $db): void
    {
        $db->insert('gr_plans', [
            'slug' => 'starter',
            'name' => 'Starter',
            'price_usd' => 10,
            'price_inr' => 820,
            'total_minutes' => 300,
            'daily_minutes_limit' => 300,
            'validity_days' => 30,
            'extends_validity_days' => 0,
            'is_top_up' => 0,
        ]);
    }

    private function seedUser(Medoo $db): void
    {
        $db->insert('site_users', [
            'user_id' => 1,
            'email' => 'founder@example.test',
            'display_name' => 'Founder',
        ]);
    }

    private function assertSame($expected, $actual, string $message = ''): void
    {
        if ($expected !== $actual) {
            throw new \RuntimeException($message ?: sprintf(
                'Failed asserting that %s matches expected %s',
                var_export($actual, true),
                var_export($expected, true)
            ));
        }
    }

    private function assertArrayHasKey(string $key, array $array, string $message = ''): void
    {
        if (!array_key_exists($key, $array)) {
            throw new \RuntimeException($message ?: "Missing expected array key '{$key}'");
        }
    }

    private function assertCount(int $expected, array $array, string $message = ''): void
    {
        if (count($array) !== $expected) {
            throw new \RuntimeException($message ?: sprintf(
                'Failed asserting count %d, got %d',
                $expected,
                count($array)
            ));
        }
    }
}

if (php_sapi_name() === 'cli' && basename(__FILE__) === basename($_SERVER['argv'][0])) {
    (new SubscriptionServiceTestSuite())->run();
}
