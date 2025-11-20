<?php

if (!function_exists('subscription_db_prefix')) {
    function subscription_db_prefix(): string
    {
        if (class_exists('Registry') && method_exists('Registry', 'stored') && \Registry::stored('config')) {
            $config = \Registry::load('config');
            if (isset($config->database)) {
                $database = $config->database;
                if (is_array($database) && array_key_exists('prefix', $database)) {
                    return (string)$database['prefix'];
                }
                if (is_object($database) && isset($database->prefix)) {
                    return (string)$database->prefix;
                }
            }
        }

        return '';
    }
}

if (!function_exists('subscription_medoo_table')) {
    function subscription_medoo_table(string $base): string
    {
        $prefix = subscription_db_prefix();

        if (!empty($prefix)) {
            return $base;
        }

        return 'gr_' . $base;
    }
}

if (!function_exists('subscription_sql_table')) {
    function subscription_sql_table(string $base): string
    {
        $prefix = subscription_db_prefix();

        if (!empty($prefix)) {
            return $prefix . $base;
        }

        return 'gr_' . $base;
    }
}

if (!function_exists('subscription_load_gateway_credentials')) {
    function subscription_load_gateway_credentials(string $identifier): ?array
    {
        if (!class_exists('DB')) {
            return null;
        }

        try {
            $record = DB::connect()->get(
                subscription_medoo_table('payment_gateways'),
                ['credentials', 'disabled'],
                ['identifier' => $identifier]
            );
        } catch (\Throwable $exception) {
            return null;
        }

        if (!$record || (int)($record['disabled'] ?? 0) === 1 || empty($record['credentials'])) {
            return null;
        }

        $decoded = json_decode($record['credentials'], true);
        return is_array($decoded) ? $decoded : null;
    }
}

if (!function_exists('subscription_resolve_razorpay_credentials')) {
    function subscription_resolve_razorpay_credentials(): ?array
    {
        $credentials = subscription_load_gateway_credentials('razorpay');
        if (!$credentials) {
            return null;
        }

        $keyId = $credentials['razorpay_api_key'] ?? $credentials['api_key'] ?? null;
        $keySecret = $credentials['razorpay_secret_key'] ?? $credentials['api_secret'] ?? null;

        if (empty($keyId) || empty($keySecret)) {
            return null;
        }

        return [
            'key_id' => $keyId,
            'key_secret' => $keySecret,
        ];
    }
}

if (!function_exists('subscription_resolve_paypal_credentials')) {
    function subscription_resolve_paypal_credentials(): ?array
    {
        $credentials = subscription_load_gateway_credentials('paypal');
        if (!$credentials) {
            return null;
        }

        $clientId = $credentials['paypal_client_id'] ?? $credentials['client_id'] ?? null;
        $clientSecret = $credentials['paypal_client_secret'] ?? $credentials['client_secret'] ?? null;
        $testMode = $credentials['paypal_test_mode'] ?? $credentials['test_mode'] ?? 'no';

        if (empty($clientId) || empty($clientSecret)) {
            return null;
        }

        return [
            'client_id' => $clientId,
            'client_secret' => $clientSecret,
            'test_mode' => $testMode === 'yes' || $testMode === true || $testMode === 1,
        ];
    }
}

if (!function_exists('subscription_get_payment_gateways_table')) {
    function subscription_get_payment_gateways_table(): string
    {
        return subscription_medoo_table('payment_gateways');
    }
}

if (!function_exists('subscription_create_razorpay_order')) {
    function subscription_create_razorpay_order(int $userId, array $plan, float $amountInr, array $credentials): array
    {
        if (empty($plan['slug'])) {
            throw new \Exception('Plan metadata missing for Razorpay order.');
        }

        $amountPaise = (int) round($amountInr * 100);
        if ($amountPaise <= 0) {
            throw new \Exception('Invalid order amount.');
        }

        $api = new \Razorpay\Api\Api($credentials['key_id'], $credentials['key_secret']);
        $order = $api->order->create([
            'amount' => $amountPaise,
            'currency' => 'INR',
            'receipt' => 'sub_' . $userId . '_' . uniqid('', true),
            'payment_capture' => 1,
            'notes' => [
                'user_id' => $userId,
                'plan_slug' => $plan['slug'],
            ],
        ]);

        return [
            'order_id' => $order['id'],
            'amount' => $order['amount'],
            'currency' => $order['currency'],
            'key' => $credentials['key_id'],
            'description' => sprintf('%s subscription recharge', $plan['name'] ?? 'Subscription'),
        ];
    }
}

if (!function_exists('subscription_create_paypal_checkout')) {
    function subscription_create_paypal_checkout(
        int $userId,
        array $plan,
        float $amount,
        array $credentials,
        string $returnUrl,
        string $cancelUrl
    ): array {
        $gateway = \Omnipay\Omnipay::create('PayPal_Rest');
        $gateway->initialize([
            'clientId' => $credentials['client_id'],
            'secret' => $credentials['client_secret'],
            'testMode' => $credentials['test_mode'],
        ]);

        $response = $gateway->purchase([
            'amount' => number_format($amount, 2, '.', ''),
            'currency' => 'USD',
            'description' => sprintf('%s subscription recharge', $plan['name'] ?? 'Subscription'),
            'returnUrl' => $returnUrl,
            'cancelUrl' => $cancelUrl,
        ])->send();

        if ($response->isRedirect()) {
            return [
                'checkout_url' => $response->getRedirectUrl(),
                'payment_id' => $response->getTransactionReference(),
            ];
        }

        throw new \Exception($response->getMessage() ?: 'Unable to create PayPal checkout session.');
    }
}
