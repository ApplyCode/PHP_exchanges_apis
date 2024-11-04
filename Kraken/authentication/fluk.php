<?php

require_once 'example.php'; // Ensure this path is correct

// Your Kraken API credentials
$key = 'your_api_key'; // Replace with your actual API key
$secret = 'your_api_secret'; // Replace with your actual API secret

// Initialize the Kraken API client
$kraken = new KrakenAPI($key, $secret);

// Define your parameters
$market = 'XXBTZUSD'; // Example market pair
$price_1 = 840; // Base price for comparison
$pcts = [0.01, 0.02, 0.03, 0.04, 0.1]; // Percentage thresholds

// Function to fetch yesterday's trades
function fetchTrades($kraken, $market) {
    $since = strtotime("-1 day") . '000000000'; // Timestamp in microseconds
    $trades = $kraken->QueryPublic('Trades', ['pair' => $market, 'since' => $since]);
    print_r($trades);
    return $trades['result'][$market];
}

// Function to analyze trades
function analyzeTrades($trades, $price_1, $pcts) {
    $fluctuations = [];
    foreach ($pcts as $pct) {
        $fluctuations[$pct] = ['positive' => ['count' => 0, 'volume' => 0], 'negative' => ['count' => 0, 'volume' => 0]];
    }

    foreach ($trades as $trade) {
        $price = $trade[0];
        $volume = $trade[1];
        foreach ($pcts as $pct) {
            if ($price >= $price_1 * (1 + $pct)) {
                $fluctuations[$pct]['positive']['count']++;
                $fluctuations[$pct]['positive']['volume'] += $volume;
            } elseif ($price <= $price_1 * (1 - $pct)) {
                $fluctuations[$pct]['negative']['count']++;
                $fluctuations[$pct]['negative']['volume'] += $volume;
            }
        }
    }

    // Calculate average volume
    foreach ($fluctuations as $pct => &$data) {
        foreach (['positive', 'negative'] as $direction) {
            if ($data[$direction]['count'] > 0) {
                $data[$direction]['average_volume'] = $data[$direction]['volume'] / $data[$direction]['count'];
            } else {
                $data[$direction]['average_volume'] = 0;
            }
        }
    }

    return $fluctuations;
}

// Main execution
try {
    $trades = fetchTrades($kraken, $market);
    $analysis = analyzeTrades($trades, $price_1, $pcts);

    // Output the results
    echo "Fluctuation Analysis for Market: $market\n";
    foreach ($analysis as $pct => $data) {
        echo "Threshold +/-" . ($pct * 100) . "%:\n";
        echo "Positive Fluctuations: {$data['positive']['count']}, Average Volume: {$data['positive']['average_volume']}\n";
        echo "Negative Fluctuations: {$data['negative']['count']}, Average Volume: {$data['negative']['average_volume']}\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
