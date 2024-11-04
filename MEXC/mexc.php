<?php

// MEXC API endpoint for the order book
$apiUrl = 'https://api.mexc.com/api/v3/aggTrades'; 
$symbol = 'BTCUSDT';
$starTime = '1707752836000';
 $endTime = '1707755839000';
$limit = 20; // Number of orders to fetch

// Initialize cURL session
$ch = curl_init();

// Set cURL options
curl_setopt($ch, CURLOPT_URL, $apiUrl . '?symbol=' . $symbol . '&limit=' . $limit . '&startTime=' . $starTime . '&endTime=' . $endTime);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HEADER, false);

// Execute cURL session and get the response
$response = curl_exec($ch);

// Close cURL session
curl_close($ch);

// Decode JSON response
$orderBook = json_decode($response, true);

// Check for errors and handle the response
if (json_last_error() === JSON_ERROR_NONE) {
    // Print order book data
    print_r($orderBook);
} else {
    echo "Error decoding JSON response.";
}

?>
