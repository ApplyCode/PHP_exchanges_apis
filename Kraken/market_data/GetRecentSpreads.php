<?php
// Get Recent Spreads

function getKrakenRecentSpreads($pair, $since = null) {
    $url = "https://api.kraken.com/0/public/Spread?pair=" . $pair;
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

    // Return the recent spreads data
    return $data;
}

// Call the function and output the recent spreads
$pair = 'XXBTZUSD'; // pair identifier for BTC/USD
$recentSpreads = getKrakenRecentSpreads($pair);
echo "Recent spreads for $pair: \n";
print_r($recentSpreads);
?>
