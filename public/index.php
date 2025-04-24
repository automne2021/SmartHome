<?php
require_once '../src/config/database.php';
require_once '../src/controllers/DeviceController.php';
require_once '../src/controllers/SensorController.php';

$deviceController = new DeviceController();
$sensorController = new SensorController();

$action = $_GET['action'] ?? '';

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
        if ($deviceId && $status !== null) {
            $deviceController->toggleDevice($deviceId, $status);
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid parameters']);
        }
        break;

    default:
        include '../src/views/dashboard.php';
        break;
}
?>