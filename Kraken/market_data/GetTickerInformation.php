<?php
//Get Ticker(Price) Information
function getKrakenTradingPairPrice($pair)
{
    $url = "https://api.kraken.com/0/public/Ticker?pair=" . $pair;

    // Use cURL for HTTP GET request
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_URL, $url);
    $result = curl_exec($ch);
    curl_close($ch);

    //JSON response
    $data = json_decode($result, true);

    if (isset($data['result'][$pair])) {
        return $data['result'][$pair]['c'][0];
    } else {
        return "Price not available for the specified trading pair.";
    }
}

// Fetch the price of the BTC/USD trading pair
$tradingPair = 'XXBTZUSD'; // Kraken's standard pair name for BTC/USD
$price = getKrakenTradingPairPrice($tradingPair);
echo "The current price of $tradingPair is: $price";
?>