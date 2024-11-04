<?php
//Get Order Book Data

function getKrakenOrderBook($pair, $count = 10) {
    $url = "https://api.kraken.com/0/public/Depth?pair=" . $pair . "&count=" . $count;

    // Initialize cURL session
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); // Return the transfer as a string
    curl_setopt($ch, CURLOPT_URL, $url); // Set the URL

    // Execute the session and close it
    $result = curl_exec($ch);
    curl_close($ch);

    // Decode JSON response
    $data = json_decode($result, true);

    // Return the Order Book data
    return $data;
}

// call the function and output the Order Book data
$pair = 'XXBTZUSD'; // kraken pair identifier for BTC/USD
$count = 10; // number of orders to retrieve for both bids and asks
$orderBookData = getKrakenOrderBook($pair, $count);
echo "Kraken Order Book Data for $pair: \n";
print_r($orderBookData);
?>
