<?php
// Get OHLC Data

function getKrakenOHLCData($pair, $interval = 1) {
    $url = "https://api.kraken.com/0/public/OHLC?pair=" . $pair . "&interval=" . $interval;

    // Initialize cURL session
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); // Return the transfer as a string
    curl_setopt($ch, CURLOPT_URL, $url); // Set the URL

    // Execute the session and close it
    $result = curl_exec($ch);
    curl_close($ch);

    // Decode JSON response
    $data = json_decode($result, true);

    // Return the OHLC data
    return $data;
}

// Call the function and output the OHLC data
$pair = 'XXBTZUSD'; // Kraken pair identifier for BTC/USD
$interval = 1; // Time frame interval in minutes
$ohlcData = getKrakenOHLCData($pair, $interval);
echo "Kraken OHLC Data for $pair: \n";
print_r($ohlcData);
?>
