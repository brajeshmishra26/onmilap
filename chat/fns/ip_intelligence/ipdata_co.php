<?php

$timeout = 8;
$banOnProbability = Registry::load('settings')->ip_intelligence_probability;

if (empty($banOnProbability) || (float)$banOnProbability > 2) {
    $banOnProbability = 0.99;
}

$curl = curl_init();
curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($curl, CURLOPT_TIMEOUT, $timeout);


$curl_url = "https://api.ipdata.co/".$ip_address;

if (!empty(Registry::load('settings')->ip_intelligence_api_key)) {
    $curl_url .= '?api-key='.Registry::load('settings')->ip_intelligence_api_key;
}

curl_setopt($curl, CURLOPT_URL, $curl_url);
$response = curl_exec($curl);
curl_close($curl);

$ipInfo = json_decode($response);

if (!empty($ipInfo) && isset($ipInfo->threat)) {
    $isBlocked = (
        $ipInfo->threat->is_tor ||
        $ipInfo->threat->is_proxy ||
        $ipInfo->threat->is_datacenter ||
        $ipInfo->threat->is_anonymous ||
        $ipInfo->threat->is_known_attacker ||
        $ipInfo->threat->is_threat
    );

    if ($isBlocked) {
        $result['success'] = false;
        $result['response'] = $ipInfo;
    } else {
        $result['success'] = true;
    }
}

?>