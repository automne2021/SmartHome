<?php
require_once 'src/config/adafruit_config.php';
require_once 'src/utils/AdafruitClient.php';

// This script tests the connection to Adafruit IO
echo "Testing connection to Adafruit IO...\n";
echo "Username: " . ADAFRUIT_USERNAME . "\n";
echo "API Key: " . substr(ADAFRUIT_API_KEY, 0, 5) . "...[hidden]\n\n";

// Create a new Adafruit client with your credentials
$client = new AdafruitClient(ADAFRUIT_API_KEY, ADAFRUIT_USERNAME);

// Test getting data from each feed
$feeds = ['door', 'lamp', 'lamp2', 'fan', 'temperature', 'humidity'];

foreach ($feeds as $feed) {
    try {
        echo "Testing feed '$feed': ";
        $data = $client->getData($feed);
        
        if (!empty($data)) {
            echo "SUCCESS!\n";
            echo "Latest value: " . $data[0]['value'] . " (at " . $data[0]['created_at'] . ")\n\n";
        } else {
            echo "WARNING: Feed exists but has no data.\n\n";
        }
    } catch (Exception $e) {
        echo "ERROR: " . $e->getMessage() . "\n\n";
    }
}

echo "Hardware connection test completed.\n";
?>