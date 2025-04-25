<?php
require_once 'src/config/database.php';
require_once 'src/config/adafruit_config.php';
require_once 'src/utils/AdafruitClient.php';
require_once 'src/controllers/DeviceController.php';

header('Content-Type: application/json');

// Validate input
$deviceId = isset($_POST['deviceId']) ? intval($_POST['deviceId']) : null;
$brightness = isset($_POST['brightness']) ? intval($_POST['brightness']) : null;

if ($deviceId === null || $brightness === null) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
    exit;
}

// Initialize controller
$deviceController = new DeviceController();

// Set brightness
$result = $deviceController->setBrightness($deviceId, $brightness);

// Return result
echo json_encode($result);
?>