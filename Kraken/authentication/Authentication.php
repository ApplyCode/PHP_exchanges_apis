<?php
// Authentication Example

function krakenApiCall($method, array $req = array()) {
    // API URL
    $url = "https://api.kraken.com/0/private/" . $method;

    // Set API key and secret
    $apiKey = 'xxx'; // Replace with your API key
    $apiSecret = 'xxx'; // Replace with your API secret

    // Generate a nonce (a unique long number)
    $req['nonce'] = time()*1000;

    // Generate POST data string
    $postdata = http_build_query($req, '', '&');

    // Decode API secret from base64
    $decodeSecret = base64_decode($apiSecret, true);

    // Generate a signature
    $signature = base64_encode(hash_hmac('sha512', $url . hash('sha256', $req['nonce'] . $postdata, true), $decodeSecret, true));

    // Set headers
    $headers = array(
        'API-Key: ' . $apiKey,
        'API-Sign: ' . $signature
    );

    // Use cURL to make the request
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postdata);

    // Execute the query and close
    $res = curl_exec($ch);
    curl_close($ch);

    // Decode and return response
    return json_decode($res, true);
}

// Replace 'Balance' with the desired endpoint
$response = krakenApiCall('Balance');
print_r($response);
?>
