<?php

namespace App\Subscription;

use DateTimeImmutable;
use Medoo\Medoo;

require_once __DIR__ . '/helpers.php';

class SubscriptionModel
{
    private string $subscriptionsTable;
    private string $subscriptionsSqlTable;
    private string $plansTable;
    private string $plansSqlTable;
    private string $topupsTable;
    private string $eventsTable;

    public function __construct(private Medoo $db)
    {
        $this->subscriptionsTable = subscription_medoo_table('user_subscriptions');
        $this->subscriptionsSqlTable = subscription_sql_table('user_subscriptions');
        $this->plansTable = subscription_medoo_table('plans');
        $this->plansSqlTable = subscription_sql_table('plans');
        $this->topupsTable = subscription_medoo_table('topups');
        $this->eventsTable = subscription_medoo_table('subscription_events');
    }

    public function findActiveForUser(int $userId, bool $forUpdate = false): ?array
    {
        $table = '`' . str_replace('`', '``', $this->subscriptionsSqlTable) . '`';
        $sql = "SELECT * FROM {$table} WHERE user_id = :user_id AND status = 'active' ORDER BY end_at DESC LIMIT 1";
        $driver = $this->db->pdo->getAttribute(\PDO::ATTR_DRIVER_NAME);
        if ($forUpdate && $driver !== 'sqlite') {
            $sql .= ' FOR UPDATE';
        }

        $statement = $this->db->pdo->prepare($sql);
        $statement->execute([':user_id' => $userId]);
        $row = $statement->fetch(\PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    public function create(array $data): int
    {
        $this->db->insert($this->subscriptionsTable, $data);
        return (int) $this->db->id();
    }

    public function findActiveWithPlan(int $userId, bool $forUpdate = false): ?array
    {
        $subscriptionsTable = '`' . str_replace('`', '``', $this->subscriptionsSqlTable) . '`';
        $plansTable = '`' . str_replace('`', '``', $this->plansSqlTable) . '`';
        $sql = "SELECT s.*, p.name AS plan_name, p.slug AS plan_slug, p.total_minutes AS plan_total_minutes, p.daily_minutes_limit AS plan_daily_minutes_limit, p.price_inr AS plan_price_inr, p.price_usd AS plan_price_usd FROM {$subscriptionsTable} AS s LEFT JOIN {$plansTable} AS p ON p.id = s.plan_id WHERE s.user_id = :user_id AND s.status = 'active' ORDER BY s.end_at DESC LIMIT 1";

        $driver = $this->db->pdo->getAttribute(\PDO::ATTR_DRIVER_NAME);
        if ($forUpdate && $driver !== 'sqlite') {
            $sql .= ' FOR UPDATE';
        }

        $statement = $this->db->pdo->prepare($sql);
        $statement->bindValue(':user_id', $userId, \PDO::PARAM_INT);
        $statement->execute();
        $row = $statement->fetch(\PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    public function update(int $id, array $data): void
    {
        $data['concurrency_token[+]'] = 1;
        $this->db->update($this->subscriptionsTable, $data, ['id' => $id]);
    }

    public function latestForUser(int $userId, int $limit = 5): array
    {
        $limit = max(1, $limit);
        $subscriptionsTable = '`' . str_replace('`', '``', $this->subscriptionsSqlTable) . '`';
        $plansTable = '`' . str_replace('`', '``', $this->plansSqlTable) . '`';
        $sql = "SELECT s.*, p.name AS plan_name, p.slug AS plan_slug, p.total_minutes AS plan_total_minutes, p.daily_minutes_limit AS plan_daily_minutes_limit, p.price_inr AS plan_price_inr, p.price_usd AS plan_price_usd FROM {$subscriptionsTable} AS s LEFT JOIN {$plansTable} AS p ON p.id = s.plan_id WHERE s.user_id = :user_id ORDER BY s.id DESC LIMIT :limit";

        $statement = $this->db->pdo->prepare($sql);
        $statement->bindValue(':user_id', $userId, \PDO::PARAM_INT);
        $statement->bindValue(':limit', $limit, \PDO::PARAM_INT);
        $statement->execute();

        $rows = $statement->fetchAll(\PDO::FETCH_ASSOC);
        return $rows ?: [];
    }

    public function insertTopUp(array $data): int
    {
        $this->db->insert($this->topupsTable, $data);
        return (int) $this->db->id();
    }

    public function logEvent(?int $subscriptionId, int $userId, string $type, array $details = []): void
    {
        $this->db->insert($this->eventsTable, [
            'subscription_id' => $subscriptionId,
            'user_id' => $userId,
            'event_type' => $type,
            'details' => !empty($details) ? json_encode($details) : null,
        ]);
    }

    public function resetDailyIfNeeded(array $subscription, DateTimeImmutable $now): void
    {
        $needsReset = empty($subscription['last_reset_at'])
            || (new DateTimeImmutable($subscription['last_reset_at']))->format('Y-m-d') !== $now->format('Y-m-d');

        if ($needsReset) {
            $this->update((int) $subscription['id'], [
                'daily_remaining_minutes' => $subscription['daily_minutes_limit'],
                'last_reset_at' => $now->format('Y-m-d H:i:s'),
            ]);
        }
    }
}
