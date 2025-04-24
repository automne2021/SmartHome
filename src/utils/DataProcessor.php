<?php
class DataProcessor {
    public static function formatSensorData($data) {
        return [
            'temperature' => isset($data['temperature']) ? floatval($data['temperature']) : null,
            'humidity' => isset($data['humidity']) ? floatval($data['humidity']) : null,
            'timestamp' => isset($data['timestamp']) ? date('Y-m-d H:i:s', strtotime($data['timestamp'])) : null,
        ];
    }

    public static function formatDeviceStatus($status) {
        return [
            'device_id' => isset($status['device_id']) ? intval($status['device_id']) : null,
            'status' => isset($status['status']) ? $status['status'] : null,
            'last_updated' => isset($status['last_updated']) ? date('Y-m-d H:i:s', strtotime($status['last_updated'])) : null,
        ];
    }

    public static function validateSensorData($data) {
        return isset($data['temperature']) && isset($data['humidity']);
    }

    public static function validateDeviceStatus($status) {
        return isset($status['device_id']) && isset($status['status']);
    }
}
?>