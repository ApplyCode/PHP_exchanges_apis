<?php
$apiKey = 'mx0vgldC9tjd5OPtva';
$apiSecret = 'db6bb3eb01d74add8fdc6498fba733b8';
$apiUrl = 'https://api.mexc.com/api/v3/order';

// Prepare the payload or parameters you need to send
$params = [
    'orderId' => '1',
    'symbol'=> 'MXUSDT',
    'recvWindow'=> '50000',
    // ... other required parameters ...
    'timestamp' => time() * 1000 // Example if timestamp is needed
];

// Sort parameters by key
ksort($params);

// Create the query string and sign it
$queryString = http_build_query($params);
$signature = hash_hmac('sha256', $queryString, $apiSecret);
$params['signature'] = $signature;

// Set up cURL
$apiUrlWithParams = $apiUrl . '?' . http_build_query($params);
print_r($apiUrlWithParams);

// Set up cURL
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $apiUrlWithParams);
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$headers = [
    "X-MEXC-APIKEY: $apiKey", // API Key in the header
    "Content-Type: application/json"
];
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

// Execute the request and close cURL
$response = curl_exec($ch);
curl_close($ch);

// Decode and handle the response
$responseData = json_decode($response, true);
if ($responseData['code'] == 200) {
    echo "Order cancelled successfully";
    print_r($responseData);
} else {
    echo "Error cancelling order: " . $responseData['msg'];
}
?>
