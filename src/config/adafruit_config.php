<?php
// Adafruit configuration settings
define('ADAFRUIT_API_KEY', 'aio_PknY103kEThd6vvHwSh8n97ah0zh');
define('ADAFRUIT_API_URL', 'https://io.adafruit.com/api/v2/');
define('ADAFRUIT_USERNAME', 'nhanphan2002');

// Function to get the Adafruit API URL for a specific feed
function getAdafruitFeedUrl($feedName) {
    return ADAFRUIT_API_URL . ADAFRUIT_USERNAME . '/feeds/' . $feedName . '/data';
}
?>