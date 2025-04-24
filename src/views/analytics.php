<?php
// analytics.php

require_once '../config/database.php';
require_once '../models/SensorData.php';

$database = new Database();
$db = $database->getConnection();

$sensorData = new SensorData($db);
$data = $sensorData->getAllSensorData();

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../assets/css/style.css">
    <title>Analytics</title>
</head>
<body>
    <div class="container">
        <h1>Sensor Data Analytics</h1>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Temperature</th>
                    <th>Humidity</th>
                    <th>Timestamp</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($data as $row): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['id']); ?></td>
                        <td><?php echo htmlspecialchars($row['temperature']); ?> °C</td>
                        <td><?php echo htmlspecialchars($row['humidity']); ?> %</td>
                        <td><?php echo htmlspecialchars($row['timestamp']); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <script src="../assets/js/main.js"></script>
</body>
</html>