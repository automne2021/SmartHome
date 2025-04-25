<?php
class DeviceController
{
    private $db;
    private $adafruitClient;
    private $settings;

    public function __construct()
    {
        require_once dirname(__FILE__) . '/../../src/config/database.php';
        require_once dirname(__FILE__) . '/../../src/config/adafruit_config.php';
        require_once dirname(__FILE__) . '/../../src/utils/AdafruitClient.php';
        require_once dirname(__FILE__) . '/../../src/utils/SystemSettings.php';

        $database = new Database();
        $this->db = $database->getConnection();
        $this->adafruitClient = new AdafruitClient(ADAFRUIT_API_KEY, ADAFRUIT_USERNAME);
        $this->settings = SystemSettings::getInstance();
    }

    public function getAllDevices()
    {
        $query = "SELECT * FROM devices ORDER BY type, name";
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function toggleDevice($deviceId, $status = null)
    {
        // First get the device details
        $query = "SELECT name, status, type, adafruit_feed, updated_at FROM devices WHERE id = :deviceId";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':deviceId', $deviceId);
        $stmt->execute();
        $device = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($device) {
            // Get current status before toggle
            $oldStatus = $device['status'];
            $oldUpdatedAt = $device['updated_at'];
            error_log("DeviceController: Device $deviceId status change - current status in DB: $oldStatus, last updated: $oldUpdatedAt");

            // If status is explicitly provided, use it; otherwise toggle
            if ($status !== null) {
                // Normalize incoming status (might be 0/1 or on/off)
                if ($status === '1' || $status === 1) {
                    $newStatus = 'on';
                } else if ($status === '0' || $status === 0) {
                    $newStatus = 'off';
                } else {
                    $newStatus = $status;
                }
            } else {
                $newStatus = ($device['status'] == 'on') ? 'off' : 'on';
            }

            // Convert status to numerical value for Adafruit IO
            $adafruitValue = ($newStatus == 'on') ? '1' : '0';

            // Debug logging
            error_log("Device toggle: ID=$deviceId, Name={$device['name']}, Feed={$device['adafruit_feed']}, NewStatus=$newStatus, AdafruitValue=$adafruitValue");

            // Send to Adafruit first (if hardware is connected)
            $adaResult = true;
            if ($this->settings->isHardwareConnected() && !empty($device['adafruit_feed'])) {
                try {
                    // Send data to Adafruit
                    $adaResult = $this->adafruitClient->sendData($device['adafruit_feed'], $adafruitValue);

                    // Log the result for debugging
                    error_log("Adafruit API response: " . json_encode($adaResult));
                } catch (Exception $e) {
                    error_log("Error sending to Adafruit: " . $e->getMessage());
                    $adaResult = false;
                }
            }

            // Only update DB if Adafruit was successful or hardware not connected
            if ($adaResult || !$this->settings->isHardwareConnected()) {
                // IMPORTANT: Only update the timestamp when turning ON, not when turning OFF
                if ($newStatus == 'on') {
                    // Update database with new timestamp
                    $updateQuery = "UPDATE devices SET status = :status, updated_at = NOW() WHERE id = :deviceId";
                } else {
                    // Keep the existing timestamp when turning off
                    $updateQuery = "UPDATE devices SET status = :status WHERE id = :deviceId";
                }

                $updateStmt = $this->db->prepare($updateQuery);
                $updateStmt->bindParam(':status', $newStatus);
                $updateStmt->bindParam(':deviceId', $deviceId);
                $dbResult = $updateStmt->execute();

                // Track device usage
                require_once dirname(__FILE__) . '/../../src/utils/DeviceUsageTracker.php';
                $tracker = new DeviceUsageTracker($this->db);
                $tracker->recordDeviceStatusChange($deviceId, $newStatus, $oldStatus);

                return [
                    'success' => $dbResult,
                    'status' => $newStatus,
                    'hardwareSent' => $adaResult && $this->settings->isHardwareConnected(),
                    'device' => $device['name'],
                    'feed' => $device['adafruit_feed']
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Failed to send command to Adafruit IO',
                    'feed' => $device['adafruit_feed']
                ];
            }
        }

        return ['success' => false, 'message' => 'Device not found'];
    }

    // Enhance your setBrightness method to provide better feedback

    public function setBrightness($deviceId, $brightness)
    {
        // First get the device details
        $query = "SELECT name, adafruit_feed FROM devices WHERE id = :deviceId AND type = 'lamp'";
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
                    // Send data to Adafruit
                    $adaResult = $this->adafruitClient->sendData($device['adafruit_feed'], $brightness);
                    error_log("Sent brightness to Adafruit: Feed={$device['adafruit_feed']}, Value=$brightness");
                } catch (Exception $e) {
                    error_log("Error sending brightness to Adafruit: " . $e->getMessage());
                    $adaResult = false;
                }
            }

            return [
                'success' => $dbResult,
                'brightness' => $brightness,
                'hardwareSent' => $adaResult && $this->settings->isHardwareConnected(),
                'device' => $device['name']
            ];
        }

        return ['success' => false, 'message' => 'Device not found or not a lamp'];
    }
}
