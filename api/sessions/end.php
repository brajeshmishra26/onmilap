<?php

declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';

header('Content-Type: application/json');

try {
    $payload = json_decode(file_get_contents('php://input'), true) ?: $_POST;
    $userId = isset($payload['user_id']) ? (int) $payload['user_id'] : 0;
    $minutes = null;

    if (isset($payload['session_minutes_used'])) {
        $minutes = (float) $payload['session_minutes_used'];
    } elseif (isset($payload['session_seconds'])) {
        $minutes = (float) $payload['session_seconds'] / 60;
    }

    if ($userId <= 0 || $minutes === null) {
        throw new InvalidArgumentException('user_id and minutes/seconds are required.');
    }

    $result = subscription_service()->deductMinutesOnSessionEnd($userId, $minutes, [
        'session_id' => $payload['session_id'] ?? null,
        'device' => $payload['device'] ?? null,
    ]);

    echo json_encode(['ok' => true, 'data' => $result]);
} catch (Throwable $exception) {
    $status = str_contains($exception->getMessage(), 'exhausted') ? 409 : 422;
    http_response_code($status);
    echo json_encode(['ok' => false, 'error' => $exception->getMessage()]);
}
