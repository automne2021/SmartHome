<?php
require_once 'src/config/database.php';
require_once 'src/config/adafruit_config.php';
require_once 'src/utils/AdafruitClient.php';
require_once 'src/utils/AdafruitDBSync.php';
require_once 'src/utils/SystemSettings.php';

$settings = SystemSettings::getInstance();
$message = '';
$testStatus = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'connect':
                $settings->setHardwareConnected(true);
                $message = 'Hardware connection enabled. Testing connection...';
                
                // Test the connection
                try {
                    $client = new AdafruitClient(ADAFRUIT_API_KEY, ADAFRUIT_USERNAME);
                    // Try to get data from temperature feed to test the connection
                    $data = $client->getData('temperature');
                    if (!empty($data)) {
                        $testStatus = 'success';
                        $message .= '<br>Connection successful! Hardware is now connected.';
                    } else {
                        $testStatus = 'warning';
                        $message .= '<br>Connection enabled, but no data received. Check your hardware.';
                    }
                } catch (Exception $e) {
                    $testStatus = 'error';
                    $message .= '<br>Error: ' . $e->getMessage();
                    $settings->setHardwareConnected(false);
                }
                break;
                
            case 'disconnect':
                $settings->setHardwareConnected(false);
                $message = 'Hardware connection disabled. System is now in offline mode.';
                break;
                
            case 'sync':
                if (!$settings->isHardwareConnected()) {
                    $message = 'Cannot sync while hardware is disconnected. Please connect hardware first.';
                    $testStatus = 'error';
                } else {
                    // Initialize database connection
                    $database = new Database();
                    $db = $database->getConnection();
                    
                    // Initialize Adafruit client
                    $adafruitClient = new AdafruitClient(ADAFRUIT_API_KEY, ADAFRUIT_USERNAME);
                    
                    // Initialize the sync utility
                    $sync = new AdafruitDBSync($adafruitClient, $db);
                    
                    // Sync all data
                    try {
                        $deviceSync = $sync->syncDeviceFromAdafruit();
                        $sensorSync = $sync->syncSensorFromAdafruit();
                        
                        if ($deviceSync && $sensorSync) {
                            $message = 'Synchronization completed successfully at ' . date('Y-m-d H:i:s');
                            $settings->updateLastSync();
                            $testStatus = 'success';
                        } else {
                            $message = 'Partial sync completed. Some data may not have been synchronized.';
                            $testStatus = 'warning';
                        }
                    } catch (Exception $e) {
                        $message = 'Error during synchronization: ' . $e->getMessage();
                        $testStatus = 'error';
                    }
                }
                break;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hardware Connection Manager</title>
    <link rel="stylesheet" href="src/assets/css/style.css">
    <style>
        .container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }
        .status-indicator {
            display: inline-block;
            width: 15px;
            height: 15px;
            border-radius: 50%;
            margin-right: 10px;
        }
        .status-connected {
            background-color: #4CAF50;
        }
        .status-disconnected {
            background-color: #F44336;
        }
        .message {
            margin: 20px 0;
            padding: 10px 15px;
            border-radius: 5px;
        }
        .message.success {
            background-color: #E8F5E9;
            border: 1px solid #4CAF50;
            color: #2E7D32;
        }
        .message.error {
            background-color: #FFEBEE;
            border: 1px solid #F44336;
            color: #C62828;
        }
        .message.warning {
            background-color: #FFF8E1;
            border: 1px solid #FFC107;
            color: #FF8F00;
        }
        .card {
            background-color: white;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            padding: 20px;
            margin-bottom: 20px;
        }
        .button-group {
            margin: 20px 0;
        }
        .button-group button {
            margin-right: 10px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Hardware Connection Manager</h1>
        
        <?php if ($message): ?>
            <div class="message <?php echo $testStatus ? $testStatus : ($settings->isHardwareConnected() ? 'success' : 'warning'); ?>">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>
        
        <div class="card">
            <h2>
                <span class="status-indicator <?php echo $settings->isHardwareConnected() ? 'status-connected' : 'status-disconnected'; ?>"></span>
                Hardware Status: <?php echo $settings->isHardwareConnected() ? 'Connected' : 'Disconnected'; ?>
            </h2>
            
            <p>Last Synchronized: <?php echo $settings->getLastSync() ? $settings->getLastSync() : 'Never'; ?></p>
            
            <div class="button-group">
                <form method="post" style="display: inline;">
                    <input type="hidden" name="action" value="<?php echo $settings->isHardwareConnected() ? 'disconnect' : 'connect'; ?>">
                    <button type="submit" class="button">
                        <?php echo $settings->isHardwareConnected() ? 'Disconnect Hardware' : 'Connect Hardware'; ?>
                    </button>
                </form>
                
                <form method="post" style="display: inline;">
                    <input type="hidden" name="action" value="sync">
                    <button type="submit" class="button" <?php echo $settings->isHardwareConnected() ? '' : 'disabled'; ?>>
                        Sync with Hardware
                    </button>
                </form>
            </div>
        </div>
        
        <div class="card">
            <h2>Instructions</h2>
            <ol>
                <li><strong>Before demo:</strong> Make sure your hardware is powered on and connected to the internet.</li>
                <li><strong>Connect Hardware:</strong> Click the "Connect Hardware" button to enable communication with your devices.</li>
                <li><strong>Sync Data:</strong> After connecting, click "Sync with Hardware" to update your database with the current device states.</li>
                <li><strong>After demo:</strong> Click "Disconnect Hardware" to put the system in offline mode.</li>
            </ol>
        </div>
        
        <a href="public/index.php" class="button">Go to Dashboard</a>
    </div>
</body>
</html>