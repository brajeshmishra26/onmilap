<?php

declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';

header('Content-Type: application/json');

try {
    $service = subscription_service();
    $rate = $service->getUsdToInr();

    if ($rate === null) {
        throw new RuntimeException('Exchange rate service disabled.');
    }

    echo json_encode([
        'ok' => true,
        'pair' => 'USDINR',
        'rate' => $rate,
        'updated_at' => gmdate(DATE_ATOM),
    ]);
} catch (Throwable $exception) {
    http_response_code(503);
    echo json_encode([
        'ok' => false,
        'error' => $exception->getMessage(),
    ]);
}
