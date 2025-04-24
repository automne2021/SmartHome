<?php

class Device {
    private $id;
    private $name;
    private $status;

    public function __construct($id, $name, $status) {
        $this->id = $id;
        $this->name = $name;
        $this->status = $status;
    }

    public function getId() {
        return $this->id;
    }

    public function getName() {
        return $this->name;
    }

    public function getStatus() {
        return $this->status;
    }

    public function setStatus($status) {
        $this->status = $status;
    }

    public function saveToDatabase($dbConnection) {
        $query = "INSERT INTO devices (id, name, status) VALUES (?, ?, ?)
                  ON DUPLICATE KEY UPDATE status = ?";
        $stmt = $dbConnection->prepare($query);
        $stmt->bind_param("issi", $this->id, $this->name, $this->status, $this->status);
        return $stmt->execute();
    }

    public static function fetchAllDevices($dbConnection) {
        $query = "SELECT * FROM devices";
        $result = $dbConnection->query($query);
        $devices = [];

        while ($row = $result->fetch_assoc()) {
            $devices[] = new Device($row['id'], $row['name'], $row['status']);
        }

        return $devices;
    }
}

?>