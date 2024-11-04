<?php

$api_url = 'https://api.kraken.com';
$api_method = '/0/private/Balance'; // Example method
$api_key = '1o1SXYgOsYVynHPcAe6lc+vcu2cGvGeS3uUjfxwCkyUS48c08esSioSL'; // Replace with your API Key
$api_secret = 'V+p2e+eP+zuvit93gfDtvoyanytFZ0QVZc3ZPdNuPDUJCOJqW74yZtSwpzkM7nCHVaLgNwlZ4acHh6cPUizlAg=='; // Replace with your API Secret

// Generate a nonce (a large, unique number)
$nonce = explode(' ', microtime());

// Generate a POST data string
$postdata = http_build_query(array('nonce' => $nonce));

// Decode API secret from base64
$api_secret = base64_decode($api_secret, true);

// Generate a signature
$signature = base64_encode(hash_hmac('sha512', $api_method . hash('sha256', $nonce . $postdata, true), $api_secret, true));

// Set up cURL
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $api_url . $api_method);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $postdata);
curl_setopt($ch, CURLOPT_HTTPHEADER, array(
    'API-Key: ' . $api_key,
    'API-Sign: ' . $signature
));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

// Execute the request and close cURL
$result = curl_exec($ch);
curl_close($ch);

// Output the result
echo $result;

?>
