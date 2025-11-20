<?php

declare(strict_types=1);

require_once __DIR__ . '/../api/bootstrap.php';

$db = DB::connect();
$pdo = $db->pdo;
$now = new DateTimeImmutable('now', new DateTimeZone('UTC'));

$pdo->beginTransaction();

$subscriptionsSqlTable = '`' . str_replace('`', '``', subscription_sql_table('user_subscriptions')) . '`';
$subscriptionsTable = subscription_medoo_table('user_subscriptions');

try {
    $stmt = $pdo->prepare("SELECT * FROM {$subscriptionsSqlTable} WHERE status = 'active' FOR UPDATE");
    $stmt->execute();

    while ($subscription = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $lastReset = $subscription['last_reset_at'] ? new DateTimeImmutable($subscription['last_reset_at'], new DateTimeZone('UTC')) : null;
        if (!$lastReset || $lastReset->format('Y-m-d') !== $now->format('Y-m-d')) {
            $db->update($subscriptionsTable, [
                'daily_remaining_minutes' => $subscription['daily_minutes_limit'],
                'last_reset_at' => $now->format('Y-m-d H:i:s'),
            ], ['id' => $subscription['id']]);
        }

        if (new DateTimeImmutable($subscription['end_at'], new DateTimeZone('UTC')) < $now) {
            $db->update($subscriptionsTable, [
                'status' => 'expired',
            ], ['id' => $subscription['id']]);
        }
    }

    $pdo->commit();
} catch (Throwable $exception) {
    $pdo->rollBack();
    error_log('[subscription_daily_reset] ' . $exception->getMessage());
    throw $exception;
}
