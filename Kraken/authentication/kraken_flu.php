<?php

require_once 'example.php'; //Input Kraken file path 

$key = 'api_key';
$secret = 'api_secret';

// Initialize the Kraken API client
$kraken = new KrakenAPI($key, $secret);

// Function to analyze trades

function analyzeTrades($trades, $price_1, $pcts)
{
    $fluctuations = array_fill_keys($pcts, ['positive' => 0, 'negative' => 0]);
    $volumes = array_fill_keys($pcts, ['positive' => 0, 'negative' => 0]);

    foreach ($trades as $trade) {
        $price = (float) $trade[0];
        $quantity = (float) $trade[1];

        foreach ($pcts as $pct) {
            $strPct = (string)$pct;
            $upperThreshold = $price_1 * (1 + $pct);
            $lowerThreshold = $price_1 * (1 - $pct);
            if ($price >= $upperThreshold) {
                $fluctuations[$strPct]['positive']++;
                $volumes[$strPct]['positive'] += $quantity;
            } elseif ($price <= $lowerThreshold) {
                $fluctuations[$strPct]['negative']++;
                $volumes[$strPct]['negative'] += $quantity;
            }
        }
    }

    // Calculate average volumes
    $averageVolumes = [];
    foreach ($pcts as $pct) {
        $strPct = (string)$pct;
        $positiveFluctuations = $fluctuations[$strPct]['positive'] ?? 0;
        $negativeFluctuations = $fluctuations[$strPct]['negative'] ?? 0;
        $positiveVolume = $volumes[$strPct]['positive'] ?? 0;
        $negativeVolume = $volumes[$strPct]['negative'] ?? 0;

        $averageVolumes[$strPct]['positive'] = $positiveFluctuations > 0 ? $positiveVolume / $positiveFluctuations : 0;
        $averageVolumes[$strPct]['negative'] = $negativeFluctuations > 0 ? $negativeVolume / $negativeFluctuations : 0;
    }

    return [$fluctuations, $averageVolumes];
}

// Main
try {
    // Define your parameters
    $market = "XXBTZUSD"; // market symbol
    $price_1 = 51000; // Base price for comparison
    $pcts = [0.01, 0.02, 0.03, 0.04, 0.05]; // Percentage thresholds

    $since = strtotime("-1 day") . '000000000'; // Timestamp in microseconds

    // Fetch yesterday's trades
    $response = $kraken->QueryPublic("Trades", [
        "pair" => $market,
        "since" => $since,
        // "count" => 530 // Adjust based on your needs
    ]);

    // Analyze the trades
    if (!isset($response['error']) || count($response['error']) == 0) {
        $trades = $response['result'][$market];
        list($fluctuations, $averageVolumes) = analyzeTrades($trades, $price_1, $pcts);

        // Output the results
        echo "Fluctuations and Average Volumes:\n";
        foreach ($pcts as $pct) {
            $strPct = (string)$pct;
            echo "PCT: " . ($pct * 100) . "%\n";
            $positiveFluctuations = $fluctuations[$strPct]['positive'] ?? 0;
            $negativeFluctuations = $fluctuations[$strPct]['negative'] ?? 0;
            $positiveAverageVolume = $averageVolumes[$strPct]['positive'] ?? 0;
            $negativeAverageVolume = $averageVolumes[$strPct]['negative'] ?? 0;

            echo "Positive Fluctuations: " . $positiveFluctuations . ", Average Volume: " . $positiveAverageVolume . "\n";
            echo "Negative Fluctuations: " . $negativeFluctuations . ", Average Volume: " . $negativeAverageVolume . "\n\n";
        }
    } else {
        // Handle API error
        echo "API Error: ";
        print_r($response['error']);
    }

} catch (APIException $e) {
    echo "Error: " . $e->getMessage();
}
