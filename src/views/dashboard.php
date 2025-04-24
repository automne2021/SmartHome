<?php
require_once '../config/database.php';
require_once '../controllers/DeviceController.php';
require_once '../controllers/SensorController.php';
require_once '../utils/SystemSettings.php';

$settings = SystemSettings::getInstance();
$deviceController = new DeviceController();
$sensorController = new SensorController();

$devices = $deviceController->getAllDevices();
$sensorData = $sensorController->getLatestSensorData();

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../assets/css/style.css">
    <title>Smart Home Dashboard</title>
    <style>
        .hardware-status {
            padding: 10px;
            margin-bottom: 20px;
            border-radius: 5px;
            text-align: center;
        }
        .hardware-connected {
            background-color: #E8F5E9;
            border: 1px solid #4CAF50;
            color: #2E7D32;
        }
        .hardware-disconnected {
            background-color: #FFEBEE;
            border: 1px solid #F44336;
            color: #C62828;
        }
        .status-indicator {
            display: inline-block;
            width: 10px;
            height: 10px;
            border-radius: 50%;
            margin-right: 5px;
        }
        .status-connected {
            background-color: #4CAF50;
        }
        .status-disconnected {
            background-color: #F44336;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Smart Home Dashboard</h1>
        
        <div class="hardware-status <?php echo $settings->isHardwareConnected() ? 'hardware-connected' : 'hardware-disconnected'; ?>">
            <span class="status-indicator <?php echo $settings->isHardwareConnected() ? 'status-connected' : 'status-disconnected'; ?>"></span>
            Hardware Status: <strong><?php echo $settings->isHardwareConnected() ? 'Connected' : 'Disconnected'; ?></strong>
            <?php if (!$settings->isHardwareConnected()): ?>
                <a href="../../hardware_connection.php" class="button" style="margin-left: 10px;">Connect Hardware</a>
            <?php endif; ?>
        </div>
        
        <section id="devices">
            <h2>Devices</h2>
            <ul>
                <?php foreach ($devices as $device): ?>
                    <li>
                        <span><?php echo $device['name']; ?> - Status: <span id="status-<?php echo $device['id']; ?>"><?php echo $device['status']; ?></span></span>
                        <button class="device-button" data-device-id="<?php echo $device['id']; ?>" data-action="<?php echo $device['status'] == 'on' ? 'turn_off' : 'turn_on'; ?>">
                            <?php echo $device['status'] == 'on' ? 'Turn Off' : 'Turn On'; ?>
                        </button>
                    </li>
                <?php endforeach; ?>
            </ul>
        </section>

        <section id="sensors">
            <h2>Sensor Data</h2>
            <p>Temperature: <span id="temperature"><?php echo isset($sensorData['temperature']) ? $sensorData['temperature'] : 'N/A'; ?></span> °C</p>
            <p>Humidity: <span id="humidity"><?php echo isset($sensorData['humidity']) ? $sensorData['humidity'] : 'N/A'; ?></span> %</p>
            <?php if (!$settings->isHardwareConnected() && ($sensorData['temperature'] == 'N/A' || $sensorData['humidity'] == 'N/A')): ?>
                <p><em>Note: Sensor data may not be available while hardware is disconnected.</em></p>
            <?php endif; ?>
        </section>
    </div>

    <script src="../assets/js/main.js"></script>
    <script>
        // Add real-time feedback about hardware connection
        document.addEventListener('DOMContentLoaded', function() {
            const hardwareConnected = <?php echo $settings->isHardwareConnected() ? 'true' : 'false'; ?>;
            
            // If hardware is disconnected, add a note to any actions
            document.querySelectorAll('.device-button').forEach(button => {
                button.addEventListener('click', function(e) {
                    if (!hardwareConnected) {
                        alert('Note: Hardware is disconnected. Changes will be saved in the database but not sent to physical devices.');
                    }
                });
            });
        });
    </script>
</body>
</html>