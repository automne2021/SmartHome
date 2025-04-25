<?php
class NotificationSystem {
    private $db;
    
    public function __construct($db) {
        $this->db = $db;
    }
    
    public function checkThresholds() {
        // Get latest temperature
        $tempQuery = "SELECT value FROM sensor_data WHERE sensor_type = 'temperature' ORDER BY timestamp DESC LIMIT 1";
        $tempStmt = $this->db->prepare($tempQuery);
        $tempStmt->execute();
        $tempRow = $tempStmt->fetch(PDO::FETCH_ASSOC);
        
        // Get latest humidity
        $humQuery = "SELECT value FROM sensor_data WHERE sensor_type = 'humidity' ORDER BY timestamp DESC LIMIT 1";
        $humStmt = $this->db->prepare($humQuery);
        $humStmt->execute();
        $humRow = $humStmt->fetch(PDO::FETCH_ASSOC);
        
        $alerts = [];
        
        // Check temperature threshold (customize these values)
        if ($tempRow && $tempRow['value'] > 30) {
            $alerts[] = [
                'type' => 'warning',
                'sensor' => 'temperature',
                'message' => 'High temperature detected: ' . $tempRow['value'] . '°C'
            ];
            $this->saveNotification('temperature_high', 'High temperature detected: ' . $tempRow['value'] . '°C');
        }
        
        // Check humidity threshold (customize these values)
        if ($humRow && $humRow['value'] > 70) {
            $alerts[] = [
                'type' => 'warning',
                'sensor' => 'humidity',
                'message' => 'High humidity detected: ' . $humRow['value'] . '%'
            ];
            $this->saveNotification('humidity_high', 'High humidity detected: ' . $humRow['value'] . '%');
        }
        
        return $alerts;
    }
    
    private function saveNotification($type, $message) {
        // Make sure the notifications table exists
        $this->createNotificationsTableIfNotExists();
        
        // Insert the notification
        $query = "INSERT INTO notifications (type, message, created_at) VALUES (:type, :message, NOW())";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':type', $type);
        $stmt->bindParam(':message', $message);
        $stmt->execute();
    }
    
    public function getNotifications($limit = 10) {
        // Make sure the notifications table exists
        $this->createNotificationsTableIfNotExists();
        
        $query = "SELECT * FROM notifications ORDER BY created_at DESC LIMIT :limit";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    private function createNotificationsTableIfNotExists() {
        $query = "CREATE TABLE IF NOT EXISTS notifications (
            id INT AUTO_INCREMENT PRIMARY KEY,
            type VARCHAR(50) NOT NULL,
            message TEXT NOT NULL,
            is_read BOOLEAN DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )";
        $this->db->exec($query);
    }
}
?>