<?php

namespace App\Subscription;

use Exception;

class ExchangeRateService
{
    private string $cacheFile;

    public function __construct(private string $apiKey, string $cacheDir)
    {
        if (!is_dir($cacheDir)) {
            @mkdir($cacheDir, 0775, true);
        }
        $this->cacheFile = rtrim($cacheDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'usd_inr.rate';
    }

    public function getUsdToInrRate(): float
    {
        if (is_file($this->cacheFile) && (time() - filemtime($this->cacheFile)) < 900) {
            return (float) file_get_contents($this->cacheFile);
        }

        $endpoint = 'https://open.er-api.com/v6/latest/USD';
        $query = http_build_query(['apikey' => $this->apiKey]);
        $response = @file_get_contents($endpoint . '?' . $query);

        if ($response === false) {
            throw new Exception('Unable to reach exchange rate provider.');
        }

        $payload = json_decode($response, true);
        $rate = (float) ($payload['rates']['INR'] ?? 0);

        if ($rate <= 0) {
            throw new Exception('Invalid USD/INR rate received.');
        }

        file_put_contents($this->cacheFile, (string) $rate);

        return $rate;
    }
}
