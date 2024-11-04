<?php

require_once 'example.php'; //Input Kraken file path 

// Your Kraken API key and secret
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
        // echo "Trade Price: $price\n";
        $quantity = (float) $trade[1];

        foreach ($pcts as $pct) {
            $strPct = (string)$pct; // Explicitly cast to string
            $upperThreshold = $price_1 * (1 + $pct);
            $lowerThreshold = $price_1 * (1 - $pct);
            // echo "PCT: $pct, Upper: $upperThreshold, Lower: $lowerThreshold\n";

            // Debugging output
            if ($price >= $upperThreshold) {
                // echo "Trade Price $price is a Positive Fluctuation for PCT $pct\n";
                $fluctuations[$strPct]['positive']++;
                $volumes[$strPct]['positive'] += $quantity;
            } elseif ($price <= $lowerThreshold) {
                // echo "Trade Price $price is a Negative Fluctuation for PCT $pct\n";
                $fluctuations[$strPct]['negative']++;
                $volumes[$strPct]['negative'] += $quantity;
            }

            // // Check for positive fluctuation
            // if ($price >= $upperThreshold) {
            //     if (!isset($fluctuations[$pct]['positive'])) {
            //         $fluctuations[$pct]['positive'] = 0;
            //     }
            //     if (!isset($volumes[$pct]['positive'])) {
            //         $volumes[$pct]['positive'] = 0;
            //     }
            //     $fluctuations[$pct]['positive']++;
            //     $volumes[$pct]['positive'] += $quantity;
            // }

            // // Check for negative fluctuation
            // if ($price <= $lowerThreshold) {
            //     if (!isset($fluctuations[$pct]['negative'])) {
            //         $fluctuations[$pct]['negative'] = 0;
            //     }
            //     if (!isset($volumes[$pct]['negative'])) {
            //         $volumes[$pct]['negative'] = 0;
            //     }
            //     $fluctuations[$pct]['negative']++;
            //     $volumes[$pct]['negative'] += $quantity;
            // }
        }
    }

    // Calculate average volumes
    $averageVolumes = [];
    foreach ($pcts as $pct) {
        $strPct = (string)$pct; // Explicitly cast to string
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
    $pcts = [0.01, 0.02, 0.03, 0.04, 0.55]; // Percentage thresholds

    $since = strtotime("-1 day") . '000000000'; // Timestamp in microseconds

    // Fetch yesterday's trades
    $response = $kraken->QueryPublic("Trades", [
        "pair" => $market,
        "since" => $since,
        // "count" => 530 // Adjust based on your needs
    ]);
    // print_r($response);

    // Analyze the trades
    if (!isset($response['error']) || count($response['error']) == 0) {
        // Assuming the response is successful and contains trades data
        $trades = $response['result'][$market]; // Adjust based on actual market key
        list($fluctuations, $averageVolumes) = analyzeTrades($trades, $price_1, $pcts);
        // echo "Trades Data:\n";
        // print_r($trades);

        // Output the results
        echo "Fluctuations and Average Volumes:\n";
        // print_r($fluctuations);
        // print_r($averageVolumes);
        foreach ($pcts as $pct) {
            $strPct = (string)$pct; // Explicitly cast to string
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
