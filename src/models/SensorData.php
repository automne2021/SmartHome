<?php

class SensorData {
    private $temperature;
    private $humidity;
    private $timestamp;

    public function __construct($temperature, $humidity, $timestamp) {
        $this->temperature = $temperature;
        $this->humidity = $humidity;
        $this->timestamp = $timestamp;
    }

    public function getTemperature() {
        return $this->temperature;
    }

    public function getHumidity() {
        return $this->humidity;
    }

    public function getTimestamp() {
        return $this->timestamp;
    }

    public function saveToDatabase($dbConnection) {
        $query = "INSERT INTO sensor_data (temperature, humidity, timestamp) VALUES (?, ?, ?)";
        $stmt = $dbConnection->prepare($query);
        $stmt->bind_param("dds", $this->temperature, $this->humidity, $this->timestamp);
        return $stmt->execute();
    }
}