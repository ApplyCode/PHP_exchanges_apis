<?php

require_once 'finaltest.php'; //Input MexC file path 

// Your MEXC API key and secret
$key = 'api_key';
$secret = 'api_secret';

// Initialize the MEXC API client
$mexc = new MEXCAPI($key, $secret);

// Function to get the start and end timestamps for yesterday
function getYesterdayTimestamps()
{
    $yesterday = new DateTime('yesterday');
    $startOfDay = (clone $yesterday)->setTime(0, 0, 0)->getTimestamp() * 1000; // Start of yesterday in milliseconds
    $endOfDay = (clone $yesterday)->setTime(0, 59, 59)->getTimestamp() * 1000; // End of yesterday in milliseconds
    return [$startOfDay, $endOfDay];
}

// Function to analyze aggregate trades

function analyzeAggregateTrades($aggTrades, $price_1, $pcts)
{
    $fluctuations = array_fill_keys($pcts, ['positive' => 0, 'negative' => 0]);
    $volumes = array_fill_keys($pcts, ['positive' => 0, 'negative' => 0]);

    foreach ($aggTrades as $trade) {
        $price = $trade['p'];
        $quantity = $trade['q'];

        foreach ($pcts as $pct) {
            $upperThreshold = $price_1 * (1 + $pct);
            $lowerThreshold = $price_1 * (1 - $pct);

            // Check for positive fluctuation
            if ($price >= $upperThreshold) {
                $fluctuations[$pct]['positive']++;
                $volumes[$pct]['positive'] += $quantity;
            }

            // Check for negative fluctuation
            if ($price <= $lowerThreshold) {
                $fluctuations[$pct]['negative']++;
                $volumes[$pct]['negative'] += $quantity;
            }
        }
    }

    // Calculate average volumes
    $averageVolumes = [];
    foreach ($pcts as $pct) {
        $averageVolumes[$pct]['positive'] = $fluctuations[$pct]['positive'] > 0 ? $volumes[$pct]['positive'] / $fluctuations[$pct]['positive'] : 0;
        $averageVolumes[$pct]['negative'] = $fluctuations[$pct]['negative'] > 0 ? $volumes[$pct]['negative'] / $fluctuations[$pct]['negative'] : 0;
    }

    return [$fluctuations, $averageVolumes];
}

// Main
try {
    // Define your parameters
    $symbol = "BTCUSDT"; // market symbol
    $price_1 = 47000; // Base price for comparison
    $pcts = [0.01, 0.02, 0.03, 0.04, 0.05]; // Percentage thresholds

    // Get yesterday's start and end timestamps
    list($yesterdayStart, $yesterdayEnd) = getYesterdayTimestamps();

    // Fetch yesterday's aggregate trades
    $aggTrades = $mexc->QueryPublic("aggTrades", [
        "symbol" => $symbol,
        "startTime" => $yesterdayStart,
        "endTime" => $yesterdayEnd,
        // "limit" => 1000 // Adjust based on your needs
    ]);
    echo "AggTrades Data:\n";
    print_r($aggTrades);

    // Analyze the aggregate trades
    list($fluctuations, $averageVolumes) = analyzeAggregateTrades($aggTrades, $price_1, $pcts);

    // Output the results
    echo "Fluctuations and Average Volumes:\n";
    foreach ($pcts as $pct) {
        echo "PCT: " . ($pct * 100) . "%\n";
        echo "Positive Fluctuations: " . $fluctuations[$pct]['positive'] . ", Average Volume: " . $averageVolumes[$pct]['positive'] . "\n";
        echo "Negative Fluctuations: " . $fluctuations[$pct]['negative'] . ", Average Volume: " . $averageVolumes[$pct]['negative'] . "\n\n";
    }

} catch (APIException $e) {
    echo "Error: " . $e->getMessage();
}
