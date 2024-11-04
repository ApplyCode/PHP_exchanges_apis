<?php
//Get System Status

function getKrakenSystemStatus() {
    $url = "https://api.kraken.com/0/public/SystemStatus";

    // Initialize cURL session
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); // Return the transfer as a string
    curl_setopt($ch, CURLOPT_URL, $url); // Set the URL

    // Execute the session and close it
    $result = curl_exec($ch);
    curl_close($ch);

    // Decode JSON response
    $data = json_decode($result, true);

    // Return the system status data
    return $data;
}

// Call the function and output the system status
$systemStatus = getKrakenSystemStatus();
echo "Kraken System Status: \n";
print_r($systemStatus);
?>
