<?php
// Get Recent Trades

function getKrakenRecentTrades($pair, $count, $since = null) {
    $url = "https://api.kraken.com/0/public/Trades?pair=" . $pair . "&count=" . $count;
    if ($since !== null) {
        $url .= "&since=" . $since;
    }

    // Initialize cURL session
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); // Return the transfer as a string
    curl_setopt($ch, CURLOPT_URL, $url); // Set the URL

    // Execute the session and close it
    $result = curl_exec($ch);
    curl_close($ch);

    // Decode JSON response
    $data = json_decode($result, true);

    // Return the recent trades data
    return $data;
}

// Call the function and output the recent trades
$pair = 'XXBTZUSD'; // pair identifier for BTC/USD
$count = 10;
$recentTrades = getKrakenRecentTrades($pair, $count);
echo "Kraken Recent Trades for $pair: \n";
print_r($recentTrades);
?>
