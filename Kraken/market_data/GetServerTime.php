<?php
// Get Server Time

function getKrakenServerTime() {
    $url = "https://api.kraken.com/0/public/Time";

    // Initialize cURL session
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); // Return the transfer as a string
    curl_setopt($ch, CURLOPT_URL, $url); // Set the URL

    // Execute the session and close it
    $result = curl_exec($ch);
    curl_close($ch);

    // Decode JSON response
    $data = json_decode($result, true);

    // Return the server time data
    return $data;
}

// Call the function and output the server time
$serverTime = getKrakenServerTime();
echo "Kraken Server Time: \n";
print_r($serverTime);
?>
