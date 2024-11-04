<?php
// Define your input parameters
$market = "YOUR_MARKET";
$price_1 = 100; // Replace with your desired starting price
$pcts = array(1, 2, 3, 4, 5); // List of percentages

// Initialize variables to store counts and average volumes
$counts_positive = array();
$counts_negative = array();
$average_volumes_positive = array();
$average_volumes_negative = array();

// Function to calculate counts and average volumes
function calculateCountsAndAverages($market, $price_1, $percentage) {
    // Add logic here to retrieve historical trading data for $market
    // You should retrieve data for the specified market and date (yesterday)

    // Initialize variables for counting and volume calculation
    $count_positive = 0;
    $count_negative = 0;
    $total_volume_positive = 0;
    $total_volume_negative = 0;

    // Loop through the trading data
    foreach ($trades as $trade) {
        // Check if the price went higher than (or equal to) pct or lower than (or equal to) -pct
        // and then returned to price_1
        if (($trade['price'] >= $price_1 + $percentage) && ($trade['price'] <= $price_1)) {
            $count_positive++;
            $total_volume_positive += $trade['volume'];
        } elseif (($trade['price'] <= $price_1 - $percentage) && ($trade['price'] >= $price_1)) {
            $count_negative++;
            $total_volume_negative += $trade['volume'];
        }
    }

    // Calculate the average volume for positive and negative fluctuations
    $average_volume_positive = ($count_positive > 0) ? $total_volume_positive / $count_positive : 0;
    $average_volume_negative = ($count_negative > 0) ? $total_volume_negative / $count_negative : 0;

    // Store the results in the global variables
    global $counts_positive, $counts_negative, $average_volumes_positive, $average_volumes_negative;
    $counts_positive[$percentage] = $count_positive;
    $counts_negative[$percentage] = $count_negative;
    $average_volumes_positive[$percentage] = $average_volume_positive;
    $average_volumes_negative[$percentage] = $average_volume_negative;
}

// Loop through each percentage and calculate counts and average volumes
foreach ($pcts as $percentage) {
    calculateCountsAndAverages($market, $price_1, $percentage);
}

// Print or use the results as needed
print_r("Counts for positive fluctuations: " . json_encode($counts_positive) . "\n");
print_r("Counts for negative fluctuations: " . json_encode($counts_negative) . "\n");
print_r("Average volumes for positive fluctuations: " . json_encode($average_volumes_positive) . "\n");
print_r("Average volumes for negative fluctuations: " . json_encode($average_volumes_negative) . "\n");
?>
