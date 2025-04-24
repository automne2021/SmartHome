<?php
require_once '../config/database.php';
require_once '../controllers/DeviceController.php';

$deviceController = new DeviceController();
$devices = $deviceController->getAllDevices();

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../assets/css/style.css">
    <title>Smart Home - Devices</title>
</head>
<body>
    <div class="container">
        <h1>Smart Home Devices</h1>
        <table>
            <thead>
                <tr>
                    <th>Device Name</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($devices as $device): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($device['name']); ?></td>
                        <td><?php echo htmlspecialchars($device['status']); ?></td>
                        <td>
                            <button onclick="toggleDevice(<?php echo $device['id']; ?>)">
                                <?php echo $device['status'] === 'on' ? 'Turn Off' : 'Turn On'; ?>
                            </button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <script src="../assets/js/main.js"></script>
    <script>
        function toggleDevice(deviceId) {
            fetch(`../controllers/DeviceController.php?action=toggle&id=${deviceId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert('Failed to toggle device status.');
                    }
                });
        }
    </script>
</body>
</html>