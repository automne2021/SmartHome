<?php
class DeviceController {
    private $db;
    private $adafruitClient;
    private $settings;

    public function __construct() {
        require_once dirname(__FILE__) . '/../../src/config/database.php';
        require_once dirname(__FILE__) . '/../../src/config/adafruit_config.php';
        require_once dirname(__FILE__) . '/../../src/utils/AdafruitClient.php';
        require_once dirname(__FILE__) . '/../../src/utils/SystemSettings.php';
        
        $database = new Database();
        $this->db = $database->getConnection();
        $this->adafruitClient = new AdafruitClient(ADAFRUIT_API_KEY, ADAFRUIT_USERNAME);
        $this->settings = SystemSettings::getInstance();
    }

    public function getAllDevices() {
        $query = "SELECT * FROM devices ORDER BY type, name";
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function toggleDevice($deviceId) {
        // First get the device details
        $query = "SELECT status, adafruit_feed FROM devices WHERE id = :deviceId";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':deviceId', $deviceId);
        $stmt->execute();
        $device = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($device) {
            // Get current status before toggle
            $oldStatus = $device['status'];
            // Toggle the status
            $newStatus = ($device['status'] == 'on') ? 'off' : 'on';
            
            // Update database
            $updateQuery = "UPDATE devices SET status = :status, updated_at = NOW() WHERE id = :deviceId";
            $updateStmt = $this->db->prepare($updateQuery);
            $updateStmt->bindParam(':status', $newStatus);
            $updateStmt->bindParam(':deviceId', $deviceId);
            $dbResult = $updateStmt->execute();

            // Track device usage
            require_once dirname(__FILE__) . '/../../src/utils/DeviceUsageTracker.php';
            $tracker = new DeviceUsageTracker($this->db);
            $tracker->recordDeviceStatusChange($deviceId, $newStatus, $oldStatus);
            
            // Send to Adafruit only if hardware is connected
            $adaResult = true;
            if ($this->settings->isHardwareConnected()) {
                try {
                    $adaResult = $this->adafruitClient->sendData($device['adafruit_feed'], $newStatus);
                } catch (Exception $e) {
                    error_log("Error sending to Adafruit: " . $e->getMessage());
                    $adaResult = false;
                }
            }
            
            return ['success' => $dbResult, 'status' => $newStatus, 'hardwareSent' => $adaResult && $this->settings->isHardwareConnected()];
        }
        
        return ['success' => false, 'message' => 'Device not found'];
    }
    
    public function setBrightness($deviceId, $brightness) {
        // First get the device details
        $query = "SELECT adafruit_feed FROM devices WHERE id = :deviceId AND type = 'lamp'";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':deviceId', $deviceId);
        $stmt->execute();
        $device = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($device) {
            // Validate brightness (0-100)
            $brightness = max(0, min(100, intval($brightness)));
            
            // Update database
            $updateQuery = "UPDATE devices SET brightness = :brightness, updated_at = NOW() WHERE id = :deviceId";
            $updateStmt = $this->db->prepare($updateQuery);
            $updateStmt->bindParam(':brightness', $brightness);
            $updateStmt->bindParam(':deviceId', $deviceId);
            $dbResult = $updateStmt->execute();
            
            // Send to Adafruit only if hardware is connected
            $adaResult = true;
            if ($this->settings->isHardwareConnected()) {
                try {
                    $adaResult = $this->adafruitClient->sendData($device['adafruit_feed'], $brightness);
                } catch (Exception $e) {
                    error_log("Error sending brightness to Adafruit: " . $e->getMessage());
                    $adaResult = false;
                }
            }
            
            return ['success' => $dbResult, 'brightness' => $brightness, 'hardwareSent' => $adaResult && $this->settings->isHardwareConnected()];
        }
        
        return ['success' => false, 'message' => 'Device not found or not a lamp'];
    }
}
?>