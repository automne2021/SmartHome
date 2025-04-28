<?php
// filepath: c:\xampp\htdocs\smarthome\api_proxy.php

require_once 'src/config/adafruit_config.php';

// Headers for JSON response
header('Content-Type: application/json');

// Get the feed name from the URL parameter
$feed = isset($_GET['feed']) ? $_GET['feed'] : null;

// Check if feed parameter is provided
if (!$feed) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing feed parameter']);
    exit;
}

// Construct the Adafruit API URL
$url = ADAFRUIT_API_URL . ADAFRUIT_USERNAME . '/feeds/' . $feed . '/data';

// Initialize cURL session
$ch = curl_init();

// Set cURL options
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'X-AIO-Key: ' . ADAFRUIT_API_KEY,
    'Content-Type: application/json'
]);

// Set timeout settings
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);

// Check if it's a POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Handle write request to Adafruit
    $jsonData = file_get_contents('php://input');
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
}

// Execute the request
$response = curl_exec($ch);

// Check for errors
if (curl_errno($ch)) {
    http_response_code(500);
    echo json_encode(['error' => 'cURL error: ' . curl_error($ch)]);
    exit;
}

// Close cURL session
curl_close($ch);

// Return the response
echo $response;
