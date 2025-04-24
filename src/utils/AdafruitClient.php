<?php
class AdafruitClient
{
    private $apiKey;
    private $username;

    public function __construct($apiKey, $username)
    {
        $this->apiKey = $apiKey;
        $this->username = $username;
    }

    public function getData($feed)
    {
        $url = "https://io.adafruit.com/api/v2/{$this->username}/feeds/{$feed}/data";
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "X-AIO-Key: {$this->apiKey}",
            'Content-Type: application/json'
        ]);
        // Add timeout settings
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);

        $response = curl_exec($ch);

        // Check for timeout or error
        if (curl_errno($ch)) {
            error_log('Curl error when contacting Adafruit: ' . curl_error($ch));
            curl_close($ch);
            return [];
        }

        curl_close($ch);

        return json_decode($response, true);
    }

    public function sendData($feed, $value)
    {
        $url = "https://io.adafruit.com/api/v2/{$this->username}/feeds/{$feed}/data";
        $data = json_encode(['value' => $value]);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "X-AIO-Key: {$this->apiKey}",
            'Content-Type: application/json'
        ]);
        // Add timeout settings
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);

        $response = curl_exec($ch);

        // Check for timeout or error
        if (curl_errno($ch)) {
            error_log('Curl error when sending to Adafruit: ' . curl_error($ch));
            curl_close($ch);
            return false;
        }

        curl_close($ch);

        return json_decode($response, true);
    }
}
