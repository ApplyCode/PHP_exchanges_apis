<?php

require_once 'finaltest.php'; //Input MexC file path 

// Your Kraken API credentials
$key = 'your_api_key'; // Replace with your actual API key
$secret = 'your_api_secret'; // Replace with your actual API secret

// Initialize the Kraken API client
$mexc = new MEXCAPI($key, $secret);

// Define your parameters
$market = 'BTCUSDT'; // Example market pair
$price_1 = 840; // Base price for comparison
$pcts = [0.01, 0.02, 0.03, 0.04, 0.1]; // Percentage thresholds

// Function to fetch yesterday's trades
function fetchTrades($mexc, $market)
{
    $yesterday = new DateTime('yesterday');
    $yesterdayStart = clone $yesterday->setTime(0, 0, 0)->getTimestamp() * 1000; // Start of yesterday in milliseconds
    $yesterdayEnd = clone $yesterday->setTime(23, 59, 59)->getTimestamp() * 1000; // End of yesterday in milliseconds
    $trades = $mexc->QueryPublic('aggTrades', ['symbol' => $market, 'startTime' => $yesterdayStart, 'endTime' => $yesterdayEnd]);
    return $trades['result'][$market];
}

// Initialize counters and volume accumulators
$fluctuations = array_fill_keys($pcts, ['positive' => 0, 'negative' => 0]);
$volumes = array_fill_keys($pcts, ['positive' => 0, 'negative' => 0]);

foreach ($trades as $trade) {
    $price = $trade['price'];
    $volume = $trade['volume'];

    foreach ($pcts as $pct) {
        $upperThreshold = $price_1 * (1 + $pct);
        $lowerThreshold = $price_1 * (1 - $pct);

        // Check for positive fluctuation
        if ($price >= $upperThreshold) {
            $fluctuations[$pct]['positive']++;
            $volumes[$pct]['positive'] += $volume;
        }

        // Check for negative fluctuation
        if ($price <= $lowerThreshold) {
            $fluctuations[$pct]['negative']++;
            $volumes[$pct]['negative'] += $volume;
        }
    }
}

// Calculate average volumes
$averageVolumes = [];
foreach ($pcts as $pct) {
    $averageVolumes[$pct]['positive'] = $fluctuations[$pct]['positive'] > 0 ? $volumes[$pct]['positive'] / $fluctuations[$pct]['positive'] : 0;
    $averageVolumes[$pct]['negative'] = $fluctuations[$pct]['negative'] > 0 ? $volumes[$pct]['negative'] / $fluctuations[$pct]['negative'] : 0;
}
