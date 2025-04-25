<?php
class DeviceUsageTracker
{
    private $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    public function recordDeviceStatusChange($deviceId, $newStatus, $oldStatus = null)
    {
        if ($oldStatus == 'on' && $newStatus == 'off') {
            // Get the last time this device was turned on
            $query = "SELECT updated_at FROM devices WHERE id = :deviceId";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':deviceId', $deviceId);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($result) {
                // Set timezone to match your MySQL server's timezone
                date_default_timezone_set('Asia/Ho_Chi_Minh'); // Adjust to match your MySQL timezone

                $lastOnTime = strtotime($result['updated_at']);
                $currentTime = time();

                // Debug time values
                error_log("Last ON time: " . date('Y-m-d H:i:s', $lastOnTime));
                error_log("Current time: " . date('Y-m-d H:i:s', $currentTime));

                $usageSeconds = max(0, ($currentTime - $lastOnTime));
                // Convert to minutes but maintain precision
                $usageMinutes = $usageSeconds / 60;

                // Add debug log for the calculated time
                error_log("DeviceUsageTracker: Device $deviceId was ON for $usageMinutes minutes (from " . date('Y-m-d H:i:s', $lastOnTime) . " to " . date('Y-m-d H:i:s', $currentTime) . ")");

                // Only record if usage is at least 5 seconds (0.083 minutes)
                if ($usageMinutes >= 0.00139) {
                    $this->addUsageRecord($deviceId, $usageMinutes);
                    error_log("DeviceUsageTracker: Usage record added for device $deviceId: $usageMinutes minutes");
                } else {
                    error_log("DeviceUsageTracker: Usage too short to record ($usageMinutes minutes)");
                }
            } else {
                error_log("DeviceUsageTracker: Could not find last update time for device $deviceId");
            }
        }
    }

    public function addUsageRecord($deviceId, $minutes)
    {
        $today = date('Y-m-d');

        // Check if we already have a record for this device today
        $checkQuery = "SELECT id, usage_minutes FROM device_usage 
                      WHERE device_id = :deviceId AND usage_date = :date";
        $checkStmt = $this->db->prepare($checkQuery);
        $checkStmt->bindParam(':deviceId', $deviceId);
        $checkStmt->bindParam(':date', $today);
        $checkStmt->execute();
        $existingRecord = $checkStmt->fetch(PDO::FETCH_ASSOC);

        if ($existingRecord) {
            // Update existing record
            $newMinutes = $existingRecord['usage_minutes'] + $minutes;
            $updateQuery = "UPDATE device_usage 
                           SET usage_minutes = :minutes 
                           WHERE id = :id";
            $updateStmt = $this->db->prepare($updateQuery);
            $updateStmt->bindParam(':minutes', $newMinutes);
            $updateStmt->bindParam(':id', $existingRecord['id']);
            $updateStmt->execute();
        } else {
            // Create new record
            $insertQuery = "INSERT INTO device_usage 
                           (device_id, usage_minutes, usage_date) 
                           VALUES (:deviceId, :minutes, :date)";
            $insertStmt = $this->db->prepare($insertQuery);
            $insertStmt->bindParam(':deviceId', $deviceId);
            $insertStmt->bindParam(':minutes', $minutes);
            $insertStmt->bindParam(':date', $today);
            $insertStmt->execute();
        }
    }

    public function calculateOngoingUsage()
    {
        // Get all devices that are currently on
        $query = "SELECT id, updated_at FROM devices WHERE status = 'on'";
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        $devices = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($devices as $device) {
            $lastOnTime = strtotime($device['updated_at']);
            $currentTime = time();
            // Calculate in minutes instead of hours
            $usageMinutes = round(($currentTime - $lastOnTime) / 60);

            if ($usageMinutes > 0) {
                $this->addUsageRecord($device['id'], $usageMinutes);
            }
        }
    }
}
