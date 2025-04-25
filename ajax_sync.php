<?php
require_once 'src/config/database.php';
require_once 'src/config/adafruit_config.php';
require_once 'src/utils/AdafruitClient.php';
require_once 'src/utils/AdafruitDBSync.php';
require_once 'src/utils/SystemSettings.php';

header('Content-Type: application/json');

// Initialize database connection
$database = new Database();
$db = $database->getConnection();

// Initialize Adafruit client
$adafruitClient = new AdafruitClient(ADAFRUIT_API_KEY, ADAFRUIT_USERNAME);

// Force hardware to be considered connected for automated sync
$settings = SystemSettings::getInstance();
$settings->setHardwareConnected(true);

// Initialize the sync utility
$sync = new AdafruitDBSync($adafruitClient, $db);

// Perform synchronization
try {
    $start = microtime(true);
    $deviceSync = $sync->syncDeviceFromAdafruit();
    $sensorSync = $sync->syncSensorFromAdafruit();
    $duration = round((microtime(true) - $start) * 1000, 2);
    
    // Return sync result
    echo json_encode([
        'success' => $deviceSync && $sensorSync,
        'deviceSync' => $deviceSync,
        'sensorSync' => $sensorSync,
        'timestamp' => date('Y-m-d H:i:s'),
        'duration' => $duration . 'ms'
    ]);
    
    // Update last sync time in settings
    if ($deviceSync && $sensorSync) {
        $settings->updateLastSync();
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>