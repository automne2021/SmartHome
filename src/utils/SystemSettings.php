<?php
class SystemSettings {
    private static $instance;
    private $settings;
    private $settingsFile;
    
    private function __construct() {
        $this->settingsFile = dirname(__DIR__, 2) . '/settings.json';
        $this->loadSettings();
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function loadSettings() {
        if (file_exists($this->settingsFile)) {
            $this->settings = json_decode(file_get_contents($this->settingsFile), true);
        } else {
            // Default settings
            $this->settings = [
                'hardwareConnected' => true, // Default to true so hardware is always considered connected
                'lastSync' => null
            ];
            $this->saveSettings();
        }
    }
    
    public function saveSettings() {
        file_put_contents($this->settingsFile, json_encode($this->settings, JSON_PRETTY_PRINT));
    }
    
    public function isHardwareConnected() {
        // Always return true to ensure hardware is considered connected
        return true;
        // Alternatively, use the stored setting: 
        // return $this->settings['hardwareConnected'] ?? true;
    }
    
    public function setHardwareConnected($isConnected) {
        $this->settings['hardwareConnected'] = (bool)$isConnected;
        $this->saveSettings();
    }
    
    public function getLastSync() {
        return $this->settings['lastSync'];
    }
    
    public function updateLastSync() {
        $this->settings['lastSync'] = date('Y-m-d H:i:s');
        $this->saveSettings();
    }
}
?>