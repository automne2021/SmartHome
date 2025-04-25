<?php
require_once 'src/config/database.php';
require_once 'src/config/adafruit_config.php';
require_once 'src/utils/AdafruitClient.php';
require_once 'src/utils/AdafruitDBSync.php';
require_once 'src/utils/SystemSettings.php';
require_once 'src/utils/NotificationSystem.php';

// Log incoming webhook requests for debugging
$logFile = __DIR__ . '/webhook_log.txt';
file_put_contents($logFile, date('Y-m-d H:i:s') . " - Webhook received\n" .
    "POST: " . json_encode($_POST) . "\n" .
    "GET: " . json_encode($_GET) . "\n", FILE_APPEND);

// Get raw JSON data
$rawInput = file_get_contents('php://input');
file_put_contents($logFile, "Raw input: " . $rawInput . "\n", FILE_APPEND);

// Process data from multiple possible sources
$feedName = null;
$value = null;

// Try to get data from URL parameters (GET)
if (isset($_GET['feed'])) {
    $feedName = $_GET['feed'];
    file_put_contents($logFile, "Feed from GET param: " . $feedName . "\n", FILE_APPEND);
}

if (isset($_GET['value'])) {
    $value = $_GET['value'];
    file_put_contents($logFile, "Value from GET param: " . $value . "\n", FILE_APPEND);
}

// Try to get data from POST
if (empty($feedName) && isset($_POST['feed'])) {
    $feedName = $_POST['feed'];
}

if ($value === null && isset($_POST['value'])) {
    $value = $_POST['value'];
}

// If not found in GET/POST, try JSON body
if (empty($feedName) || $value === null) {
    $jsonData = json_decode($rawInput, true);
    file_put_contents($logFile, "Parsed JSON: " . json_encode($jsonData) . "\n", FILE_APPEND);

    if ($jsonData) {
        // Format 1: Direct key-value
        if (isset($jsonData['feed_key']) && empty($feedName)) {
            $feedName = $jsonData['feed_key'];
        } elseif (isset($jsonData['feed']) && empty($feedName)) {
            $feedName = $jsonData['feed'];
        }

        if (isset($jsonData['value']) && $value === null) {
            $value = $jsonData['value'];
        }

        // Format 2: Array format (as seen in your logs)
        if (is_array($jsonData) && isset($jsonData[0])) {
            if (isset($jsonData[0]['feed_key']) && empty($feedName)) {
                $feedName = $jsonData[0]['feed_key'];

                // Handle template placeholders
                if ($feedName === '{{feed.key}}' && isset($_GET['feed'])) {
                    $feedName = $_GET['feed'];
                }
            }

            if (isset($jsonData[0]['value']) && $value === null) {
                $value = $jsonData[0]['value'];
            }
        }
    }
}

// If feed name still contains template placeholder, try to extract from URL
if ($feedName === '{{feed.key}}' && strpos($_SERVER['REQUEST_URI'], 'feed=') !== false) {
    preg_match('/feed=([^&]+)/', $_SERVER['REQUEST_URI'], $matches);
    if (isset($matches[1])) {
        $feedName = $matches[1];
    }
}

// Log the final extracted values
file_put_contents($logFile, "Final feed name: " . ($feedName ?? "null") . "\n", FILE_APPEND);
file_put_contents($logFile, "Final value: " . ($value ?? "null") . "\n", FILE_APPEND);

// If we don't have the required information, exit
if (empty($feedName) || $value === null) {
    file_put_contents($logFile, "Error: Missing feed_key or value\n", FILE_APPEND);
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
    exit;
}

// Create database connection
$database = new Database();
$db = $database->getConnection();

if (!$db) {
    file_put_contents($logFile, "Error: Database connection failed\n", FILE_APPEND);
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database connection error']);
    exit;
}

// Create AdafruitClient instance
$adafruitClient = new AdafruitClient(ADAFRUIT_API_KEY, ADAFRUIT_USERNAME);

// Create the sync utility
$syncUtil = new AdafruitDBSync($adafruitClient, $db);

// Force hardware to be considered connected
$settings = SystemSettings::getInstance();
$settings->setHardwareConnected(true);

// Process based on feed type
$success = false;
$message = '';

try {
    // Handle sensor data feeds
    if ($feedName == 'temperature' || $feedName == 'humidity') {
        $success = $syncUtil->saveSensorData($feedName, $value, $feedName);
        $message = "Saved $feedName data: $value";

        // Check if we need to create notifications
        if ($success) {
            $notificationSystem = new NotificationSystem($db);
            $notificationSystem->checkThresholds();
        }
    }
    // Handle device feeds
    else {
        // First check if this feed is linked to a device
        $query = "SELECT id, type FROM devices WHERE adafruit_feed = :feed";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':feed', $feedName);
        $stmt->execute();
        $device = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($device) {
            if ($device['type'] === 'lamp' && $feedName === 'lamp2') {
                $success = $syncUtil->updateDeviceBrightness($device['id'], $value);
                $message = "Updated device brightness to $value";
            } else {
                // Normalize value - this is key!
                $normalizedValue = ($value === '1' || $value === 1) ? 'on' : 'off';
                $success = $syncUtil->updateDeviceStatus($device['id'], $normalizedValue);
                $message = "Updated device status to $normalizedValue";
            }
        } else {
            $message = "No device found with feed: $feedName";
        }
    }

    // Update last sync time
    if ($success) {
        $settings->updateLastSync();
    }

    // Log the result
    file_put_contents($logFile, date('Y-m-d H:i:s') . " - Result: " .
        ($success ? "Success" : "Failed") . " - $message\n", FILE_APPEND);

    // Return success response
    http_response_code(200);
    echo json_encode([
        'success' => $success,
        'message' => $message
    ]);
} catch (Exception $e) {
    // Log error
    file_put_contents($logFile, date('Y-m-d H:i:s') . " - Error: " . $e->getMessage() . "\n", FILE_APPEND);

    // Return error response
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error processing webhook: ' . $e->getMessage()
    ]);
}
