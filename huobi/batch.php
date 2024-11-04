<?php

$apiKey = '6718d96c-1dc72f1a-bvrge3rf7j-5a3a5';
$secretKey = '3aca730a-e8f81396-086d45aa-37898';
$accountId = '60885837';

// The endpoint URL for batch orders - this might change, refer to the latest API docs
$endpoint = 'https://api.huobi.pro/v1/order/batch-orders';

// Prepare the batch orders data
// Ensure your data structure matches the expected API format
$ordersData = [
    'orders_data' => [
        [
            'account-id' => $accountId,
            'amount' => '0.01',
            'price' => '10000',
            'symbol' => 'btcusdt',
            'type' => 'buy-limit',
        ],
        [
            'account-id' => $accountId,
            'amount' => '0.02',
            'price' => '20000',
            'symbol' => 'btcusdt',
            'type' => 'sell-limit',
        ],
        // Add more orders as needed
    ]
];

function createSignature($method, $endpoint, $secretKey, $params = []) {
    $params['SignatureMethod'] = 'HmacSHA256';
    $params['SignatureVersion'] = '2';
    $params['AccessKeyId'] = $GLOBALS['apiKey'];
    $params['Timestamp'] = gmdate('Y-m-d\TH:i:s');
    ksort($params);
    $paramString = http_build_query($params);
    $paramString = str_replace(['+', '%7E'], ['%20', '~'], $paramString);
    $stringToSign = "{$method}\n" . parse_url($endpoint, PHP_URL_HOST) . "\n" . parse_url($endpoint, PHP_URL_PATH) . "\n" . $paramString;
    $sign = hash_hmac('sha256', $stringToSign, $secretKey, true);
    return base64_encode($sign);
}

function placeBatchOrders($endpoint, $ordersData, $apiKey, $secretKey) {
    $method = 'POST';
    $params = [];
    $body = json_encode($ordersData);
    $params['Signature'] = createSignature($method, $endpoint, $secretKey, $params);
    $url = $endpoint . '?' . http_build_query($params);

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
    ]);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $response = curl_exec($ch);
    if (!$response) {
        die('Error: "' . curl_error($ch) . '" - Code: ' . curl_errno($ch));
    }
    curl_close($ch);

    return json_decode($response, true);
}

// Place the batch of orders
$response = placeBatchOrders($endpoint, $ordersData, $apiKey, $secretKey);

print_r($response);
