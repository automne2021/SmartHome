<?php
require_once dirname(__FILE__) . '/SystemSettings.php';

class AdafruitDBSync
{
    private $adafruitClient;
    private $db;
    private $settings;

    public function __construct($adafruitClient, $db)
    {
        $this->adafruitClient = $adafruitClient;
        $this->db = $db;
        $this->settings = SystemSettings::getInstance();
    }

    // Sync device status from Adafruit to database
    public function syncDeviceFromAdafruit()
    {
        if (!$this->settings->isHardwareConnected()) {
            return false; // Hardware not connected, skip sync
        }

        try {
            // Get all devices from database
            $query = "SELECT id, name, type, adafruit_feed FROM devices";
            $stmt = $this->db->prepare($query);
            $stmt->execute();
            $devices = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Build a list of unique feeds to fetch
            $feeds = array_column($devices, 'adafruit_feed');
            $feedData = [];

            // Fetch each feed only once
            foreach ($feeds as $feed) {
                if (empty($feed)) continue;

                $data = $this->adafruitClient->getData($feed);
                if (!empty($data) && isset($data[0]['value'])) {
                    $feedData[$feed] = $data[0]['value'];
                }
            }

            // Now process each device
            foreach ($devices as $device) {
                if (isset($feedData[$device['adafruit_feed']])) {
                    $value = $feedData[$device['adafruit_feed']];

                    switch ($device['type']) {
                        case 'lamp':
                            if ($device['adafruit_feed'] == 'lamp2') {
                                $this->updateDeviceBrightness($device['id'], $value);
                            } else {
                                $this->updateDeviceStatus($device['id'], $value);
                            }
                            break;
                        default:
                            $this->updateDeviceStatus($device['id'], $value);
                            break;
                    }
                }
            }

            $this->settings->updateLastSync();
            return true;
        } catch (Exception $e) {
            error_log("Error syncing with Adafruit: " . $e->getMessage());
            return false;
        }
    }

    // Sync sensor data from Adafruit to database
    public function syncSensorFromAdafruit()
    {
        if (!$this->settings->isHardwareConnected()) {
            return false; // Hardware not connected, skip sync
        }

        try {
            // Sync temperature
            $tempData = $this->adafruitClient->getData('temperature');
            if (!empty($tempData) && isset($tempData[0]['value'])) {
                $this->saveSensorData('temperature', $tempData[0]['value'], 'temperature');
            }

            // Sync humidity
            $humidData = $this->adafruitClient->getData('humidity');
            if (!empty($humidData) && isset($humidData[0]['value'])) {
                $this->saveSensorData('humidity', $humidData[0]['value'], 'humidity');
            }

            return true;
        } catch (Exception $e) {
            error_log("Error syncing sensor data: " . $e->getMessage());
            return false;
        }
    }

    public function updateDeviceStatus($deviceId, $status)
    {
        $query = "UPDATE devices SET status = :status, updated_at = NOW() WHERE id = :id";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':status', $status);
        $stmt->bindParam(':id', $deviceId);
        return $stmt->execute();
    }

    public function updateDeviceBrightness($deviceId, $brightness)
    {
        $query = "UPDATE devices SET brightness = :brightness, updated_at = NOW() WHERE id = :id";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':brightness', $brightness);
        $stmt->bindParam(':id', $deviceId);
        return $stmt->execute();
    }

    public function saveSensorData($type, $value, $feed)
    {
        $query = "INSERT INTO sensor_data (sensor_type, value, adafruit_feed) VALUES (:type, :value, :feed)";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':type', $type);
        $stmt->bindParam(':value', $value);
        $stmt->bindParam(':feed', $feed);
        return $stmt->execute();
    }
}
