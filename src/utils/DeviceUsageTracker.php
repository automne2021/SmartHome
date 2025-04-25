<?php
class DeviceUsageTracker {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }
    
    public function recordDeviceStatusChange($deviceId, $newStatus, $oldStatus = null) {
        // If we're turning a device off, calculate and save the usage time
        if ($oldStatus == 'on' && $newStatus == 'off') {
            // Get the last time this device was turned on
            $query = "SELECT updated_at FROM devices WHERE id = :deviceId";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':deviceId', $deviceId);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result) {
                $lastOnTime = strtotime($result['updated_at']);
                $currentTime = time();
                $usageHours = ($currentTime - $lastOnTime) / 3600; // Convert seconds to hours
                
                // Only record if usage is at least 1 minute (0.0167 hours)
                if ($usageHours >= 0.0167) {
                    $this->addUsageRecord($deviceId, $usageHours);
                }
            }
        }
    }
    
    public function addUsageRecord($deviceId, $hours) {
        $today = date('Y-m-d');
        
        // Check if we already have a record for this device today
        $checkQuery = "SELECT id, usage_hours FROM device_usage 
                      WHERE device_id = :deviceId AND usage_date = :date";
        $checkStmt = $this->db->prepare($checkQuery);
        $checkStmt->bindParam(':deviceId', $deviceId);
        $checkStmt->bindParam(':date', $today);
        $checkStmt->execute();
        $existingRecord = $checkStmt->fetch(PDO::FETCH_ASSOC);
        
        if ($existingRecord) {
            // Update existing record
            $newHours = $existingRecord['usage_hours'] + $hours;
            $updateQuery = "UPDATE device_usage 
                           SET usage_hours = :hours 
                           WHERE id = :id";
            $updateStmt = $this->db->prepare($updateQuery);
            $updateStmt->bindParam(':hours', $newHours);
            $updateStmt->bindParam(':id', $existingRecord['id']);
            $updateStmt->execute();
        } else {
            // Create new record
            $insertQuery = "INSERT INTO device_usage 
                           (device_id, usage_hours, usage_date) 
                           VALUES (:deviceId, :hours, :date)";
            $insertStmt = $this->db->prepare($insertQuery);
            $insertStmt->bindParam(':deviceId', $deviceId);
            $insertStmt->bindParam(':hours', $hours);
            $insertStmt->bindParam(':date', $today);
            $insertStmt->execute();
        }
    }
    
    public function calculateOngoingUsage() {
        // Get all devices that are currently on
        $query = "SELECT id, updated_at FROM devices WHERE status = 'on'";
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        $devices = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($devices as $device) {
            $lastOnTime = strtotime($device['updated_at']);
            $currentTime = time();
            $usageHours = ($currentTime - $lastOnTime) / 3600;
            
            if ($usageHours > 0) {
                $this->addUsageRecord($device['id'], $usageHours);
            }
        }
    }
}