<?php
require_once dirname(__FILE__) . '/env_loader.php';

// Adafruit configuration settings
define('ADAFRUIT_API_KEY', getenv('ADAFRUIT_API_KEY'));
define('ADAFRUIT_API_URL', 'https://io.adafruit.com/api/v2/');
define('ADAFRUIT_USERNAME', getenv('ADAFRUIT_USERNAME'));

// Function to get the Adafruit API URL for a specific feed
function getAdafruitFeedUrl($feedName)
{
    return ADAFRUIT_API_URL . ADAFRUIT_USERNAME . '/feeds/' . $feedName . '/data';
}
