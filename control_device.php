<?php
require_once 'src/config/database.php';
require_once 'src/config/adafruit_config.php';
require_once 'src/utils/AdafruitClient.php';

// Get parameters from request
$feed = $_GET['feed'] ?? null;
$value = $_GET['value'] ?? null;

if (!$feed || $value === null) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing parameters']);
    exit;
}

// Initialize Adafruit client
$adafruitClient = new AdafruitClient(ADAFRUIT_API_KEY, ADAFRUIT_USERNAME);

try {
    // Send data to Adafruit IO directly
    $result = $adafruitClient->sendData($feed, $value);

    // Return result
    echo json_encode([
        'success' => true,
        'feed' => $feed,
        'value' => $value,
        'response' => $result
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
