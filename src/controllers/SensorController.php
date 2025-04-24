<?php
class SensorController {
    private $db;

    public function __construct() {
        require_once '../src/config/database.php';
        $database = new Database();
        $this->db = $database->getConnection();
    }
    
    public function getLatestSensorData() {
        $result = [];
        
        // Get latest temperature
        $tempQuery = "SELECT value FROM sensor_data WHERE sensor_type = 'temperature' ORDER BY timestamp DESC LIMIT 1";
        $tempStmt = $this->db->prepare($tempQuery);
        $tempStmt->execute();
        $tempRow = $tempStmt->fetch(PDO::FETCH_ASSOC);
        $result['temperature'] = $tempRow ? $tempRow['value'] : 'N/A';
        
        // Get latest humidity
        $humQuery = "SELECT value FROM sensor_data WHERE sensor_type = 'humidity' ORDER BY timestamp DESC LIMIT 1";
        $humStmt = $this->db->prepare($humQuery);
        $humStmt->execute();
        $humRow = $humStmt->fetch(PDO::FETCH_ASSOC);
        $result['humidity'] = $humRow ? $humRow['value'] : 'N/A';
        
        return $result;
    }

    public function fetchSensorData() {
        $query = "SELECT * FROM sensor_data ORDER BY timestamp DESC LIMIT 10";
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function saveSensorData($temperature, $humidity) {
        $query = "INSERT INTO sensor_data (temperature, humidity, timestamp) VALUES (:temperature, :humidity, NOW())";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':temperature', $temperature);
        $stmt->bindParam(':humidity', $humidity);
        return $stmt->execute();
    }
}
?>