<?php
require_once '../src/config/database.php';
require_once '../src/controllers/DeviceController.php';
require_once '../src/controllers/SensorController.php';
require_once '../src/controllers/AuthController.php';
require_once '../src/utils/SystemSettings.php';
require_once '../src/utils/NotificationSystem.php';

// Check if user is logged in (add this section)
$authController = new AuthController();
if (!$authController->isLoggedIn()) {
    header('Location: login.php');
    exit;
}

// Ensure hardware is always considered connected
$settings = SystemSettings::getInstance();
$settings->setHardwareConnected(true);

// Initialize controllers
$deviceController = new DeviceController();
$sensorController = new SensorController();

// Initialize notification system
$database = new Database();
$db = $database->getConnection();
$notificationSystem = new NotificationSystem($db);
$notifications = $notificationSystem->getNotifications(5);
$alerts = $notificationSystem->checkThresholds();

// Get action from URL parameter
$action = $_GET['action'] ?? 'dashboard';

// Process AJAX requests first
if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
    header('Content-Type: application/json');

    switch ($action) {
        case 'getDevices':
            $devices = $deviceController->getAllDevices();
            echo json_encode($devices);
            break;

        case 'getSensorData':
            $sensorData = $sensorController->getLatestSensorData();
            echo json_encode($sensorData);
            break;

        case 'toggleDevice':
            $deviceId = $_POST['deviceId'] ?? null;
            $status = $_POST['status'] ?? null;
            if ($deviceId) {
                $result = $deviceController->toggleDevice($deviceId, $status);
                echo json_encode($result);
            } else {
                echo json_encode(['success' => false, 'message' => 'Invalid parameters']);
            }
            break;

        case 'getNotifications':
            echo json_encode($notifications);
            break;

        case 'setBrightness':
            $deviceId = $_POST['deviceId'] ?? null;
            $brightness = $_POST['brightness'] ?? null;

            // Log the request for debugging
            error_log("setBrightness called. deviceId=$deviceId, brightness=$brightness");

            if ($deviceId !== null && $brightness !== null) {
                try {
                    $result = $deviceController->setBrightness($deviceId, $brightness);
                    echo json_encode($result);
                } catch (Exception $e) {
                    echo json_encode([
                        'success' => false,
                        'message' => 'Error: ' . $e->getMessage()
                    ]);
                }
            } else {
                echo json_encode(['success' => false, 'message' => 'Missing parameters']);
            }
            break;

        default:
            echo json_encode(['success' => false, 'message' => 'Unknown action']);
            break;
    }
    exit;
}

// Handle regular page requests
switch ($action) {
    case 'dashboard':
        $pageTitle = 'SmartHome Dashboard';
        $page = 'dashboard';
        $devices = $deviceController->getAllDevices();
        $sensorData = $sensorController->getLatestSensorData();
        $additionalCss = ['dashboard', 'devices', 'analytics', 'notifications'];
        $contentView = '../src/views/dashboard_content.php';
        break;

    case 'devices':
        $pageTitle = 'Manage Devices';
        $page = 'devices';
        $devices = $deviceController->getAllDevices();
        // Group devices by type
        $devicesByType = [];
        foreach ($devices as $device) {
            $devicesByType[$device['type']][] = $device;
        }
        $additionalCss = ['devices'];
        $additionalJs = ['devices'];
        $contentView = '../src/views/devices_content.php';
        break;

    case 'analytics':
        $pageTitle = 'Analytics';
        $page = 'analytics';
        // Get temperature data for the last 24 hours
        $tempQuery = "SELECT value, timestamp FROM sensor_data 
                    WHERE sensor_type = 'temperature' 
                    AND timestamp > DATE_SUB(NOW(), INTERVAL 24 HOUR)
                    ORDER BY timestamp ASC";
        $tempStmt = $db->prepare($tempQuery);
        $tempStmt->execute();
        $tempData = $tempStmt->fetchAll(PDO::FETCH_ASSOC);

        // Get humidity data for the last 24 hours
        $humQuery = "SELECT value, timestamp FROM sensor_data 
                    WHERE sensor_type = 'humidity' 
                    AND timestamp > DATE_SUB(NOW(), INTERVAL 24 HOUR)
                    ORDER BY timestamp ASC";
        $humStmt = $db->prepare($humQuery);
        $humStmt->execute();
        $humData = $humStmt->fetchAll(PDO::FETCH_ASSOC);

        // Format data for charts
        $temperatureValues = [];
        $temperatureLabels = [];
        $humidityValues = [];
        $humidityLabels = [];

        foreach ($tempData as $data) {
            $temperatureValues[] = $data['value'];
            $temperatureLabels[] = date('H:i', strtotime($data['timestamp']));
        }

        foreach ($humData as $data) {
            $humidityValues[] = $data['value'];
            $humidityLabels[] = date('H:i', strtotime($data['timestamp']));
        }

        $additionalCss = ['analytics'];
        $additionalJs = ['analytics'];
        $contentView = '../src/views/analytics_content.php';
        break;

    case 'settings':
        $pageTitle = 'Settings';
        $page = 'settings';
        $additionalCss = ['settings'];
        $contentView = '../src/views/settings_content.php';
        break;

    default:
        $pageTitle = 'Page Not Found';
        $page = '404';
        $contentView = '../src/views/404.php';
        break;
}

// Include main layout
include '../src/views/layouts/main_layout.php';
