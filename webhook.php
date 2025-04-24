<?php
require_once 'src/config/database.php';
require_once 'src/utils/AdafruitClient.php';
require_once 'src/utils/AdafruitDBSync.php';

// Get webhook data
$data = json_decode(file_get_contents('php://input'), true);
$headers = getallheaders();

// Simple authentication using a shared secret (should be configured in Adafruit)
$webhookSecret = 'your_webhook_secret_here'; // Set this to a strong secret
if (!isset($headers['X-Webhook-Secret']) || $headers['X-Webhook-Secret'] !== $webhookSecret) {
    http_response_code(403);
    exit('Unauthorized');
}

// Create database connection
$database = new Database();
$db = $database->getConnection();

// Get feed info from the webhook data
if (isset($data['feed_key']) && isset($data['value'])) {
    $feed = $data['feed_key'];
    $value = $data['value'];

    // Create AdafruitClient instance
    require_once 'src/config/adafruit_config.php';
    $adafruitClient = new AdafruitClient(ADAFRUIT_API_KEY, ADAFRUIT_USERNAME);

    // Create the sync utility
    $syncUtil = new AdafruitDBSync($adafruitClient, $db);

    // Handle different feed types
    switch ($feed) {
        case 'temperature':
        case 'humidity':
            $syncUtil->saveSensorData($feed, $value, $feed);
            break;

        default:
            // Assume it's a device feed
            // First check if this feed is linked to a device
            $query = "SELECT id, type FROM devices WHERE adafruit_feed = :feed";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':feed', $feed);
            $stmt->execute();
            $device = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($device) {
                if ($device['type'] === 'lamp' && $feed === 'lamp2') {
                    $syncUtil->updateDeviceBrightness($device['id'], $value);
                } else {
                    $syncUtil->updateDeviceStatus($device['id'], $value);
                }
            }
            break;
    }

    http_response_code(200);
    echo json_encode(['success' => true, 'message' => "Processed update for feed: $feed"]);
} else {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid webhook data']);
}
