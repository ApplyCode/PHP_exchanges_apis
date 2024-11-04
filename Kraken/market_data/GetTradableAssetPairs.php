<?php
// Get Tradable Asset Pairs

function getKrakenTradableAssetPairs($pairs) {
    $url = "https://api.kraken.com/0/public/AssetPairs?pair=" . $pairs;

    // Initialize cURL session
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); // Return the transfer as a string
    curl_setopt($ch, CURLOPT_URL, $url); // Set the URL

    // Execute the session and close it
    $result = curl_exec($ch);
    curl_close($ch);

    // Decode JSON response
    $data = json_decode($result, true);

    // Return the tradable asset pairs data
    return $data;
}

// Call the function and output the tradable asset pairs
$specificPairs = 'XXBTZUSD,XETHXXBT';
$tradableAssetPairs = getKrakenTradableAssetPairs($specificPairs);
echo "Kraken Tradable Asset Pairs for $specificPairs: \n";
print_r($tradableAssetPairs);
?>
