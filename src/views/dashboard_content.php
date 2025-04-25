<?php
// Add this normalization function at the top of the file
function normalizeDeviceStatus($status)
{
    if ($status === '1' || $status === 1) {
        return 'on';
    } else if ($status === '0' || $status === 0) {
        return 'off';
    }
    return $status;
}
?>

<div class="dashboard-header-content">
    <h1><i class="fas fa-tachometer-alt"></i> Dashboard</h1>
    <div class="dashboard-overview">
        <div class="overview-item">
            <i class="fas fa-plug"></i>
            <span>Devices: <?php echo count($devices); ?></span>
        </div>
        <div class="overview-item">
            <i class="fas fa-clock"></i>
            <span>Updated: <span id="last-update-time"><?php echo date('H:i:s'); ?></span></span>
        </div>
    </div>
</div>

<div class="dashboard-grid">
    <!-- Current Conditions Card -->
    <div class="card sensor-summary-card">
        <h2><i class="fas fa-thermometer-half"></i> Current Conditions</h2>
        <div class="sensor-grid">
            <div class="sensor-card">
                <div class="sensor-icon">
                    <i class="fas fa-temperature-high"></i>
                </div>
                <div class="sensor-data">
                    <h3>Temperature</h3>
                    <div class="sensor-value">
                        <span id="temperature"><?php echo isset($sensorData['temperature']) ? $sensorData['temperature'] : 'N/A'; ?></span>
                        <span class="sensor-unit">°C</span>
                    </div>
                    <?php if (isset($sensorData['temperature'])): ?>
                        <?php $tempStatus = $sensorData['temperature'] > 30 ? 'high' : ($sensorData['temperature'] < 18 ? 'low' : 'normal'); ?>
                        <div class="sensor-status <?php echo $tempStatus; ?>">
                            <?php
                            if ($tempStatus == 'high') echo '<i class="fas fa-arrow-up"></i> High';
                            elseif ($tempStatus == 'low') echo '<i class="fas fa-arrow-down"></i> Low';
                            else echo '<i class="fas fa-check"></i> Normal';
                            ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            <div class="sensor-card">
                <div class="sensor-icon">
                    <i class="fas fa-tint"></i>
                </div>
                <div class="sensor-data">
                    <h3>Humidity</h3>
                    <div class="sensor-value">
                        <span id="humidity"><?php echo isset($sensorData['humidity']) ? $sensorData['humidity'] : 'N/A'; ?></span>
                        <span class="sensor-unit">%</span>
                    </div>
                    <?php if (isset($sensorData['humidity'])): ?>
                        <?php $humStatus = $sensorData['humidity'] > 70 ? 'high' : ($sensorData['humidity'] < 30 ? 'low' : 'normal'); ?>
                        <div class="sensor-status <?php echo $humStatus; ?>">
                            <?php
                            if ($humStatus == 'high') echo '<i class="fas fa-arrow-up"></i> High';
                            elseif ($humStatus == 'low') echo '<i class="fas fa-arrow-down"></i> Low';
                            else echo '<i class="fas fa-check"></i> Normal';
                            ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <div class="analytics-link">
            <a href="index.php?action=analytics" class="btn btn-outline">
                <i class="fas fa-chart-line btn-icon"></i> View Analytics
            </a>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="card action-card">
        <h2><i class="fas fa-bolt"></i> Quick Actions</h2>
        <div class="quick-action-buttons">
            <button class="btn btn-action" id="refresh-all-btn">
                <i class="fas fa-sync-alt"></i>
                <span>Refresh All</span>
            </button>
            <button class="btn btn-action" id="toggle-all-off-btn">
                <i class="fas fa-power-off"></i>
                <span>All Off</span>
            </button>
            <a href="index.php?action=devices" class="btn btn-action">
                <i class="fas fa-cogs"></i>
                <span>Manage Devices</span>
            </a>
            <a href="index.php?action=settings" class="btn btn-action">
                <i class="fas fa-sliders-h"></i>
                <span>Settings</span>
            </a>
        </div>
    </div>
</div>

<!-- Devices Grid -->
<div class="card">
    <div class="card-header-with-actions">
        <h2><i class="fas fa-lightbulb"></i> Devices</h2>
        <div class="card-actions">
            <a href="index.php?action=devices" class="btn btn-sm">
                <i class="fas fa-external-link-alt"></i> All Devices
            </a>
        </div>
    </div>

    <div class="devices-grid">
        <?php foreach ($devices as $device):
            $normalizedStatus = normalizeDeviceStatus($device['status']);
            // Check if this is the special lamp2 with brightness
            $isLamp2 = ($device['type'] == 'lamp' && $device['adafruit_feed'] == 'lamp2');
        ?>
            <div class="device-card <?php echo $normalizedStatus == 'on' ? 'device-on' : 'device-off'; ?>">
                <?php
                $icon = 'question';
                switch ($device['type']) {
                    case 'lamp':
                        $icon = 'lightbulb';
                        break;
                    case 'fan':
                        $icon = 'fan';
                        break;
                    case 'door':
                        $icon = 'door-open';
                        break;
                }
                ?>
                <div class="device-icon-wrapper">
                    <i class="fas fa-<?php echo $icon; ?>"></i>
                </div>
                <div class="device-info">
                    <h3><?php echo htmlspecialchars($device['name']); ?></h3>
                    <div class="device-status-indicator">
                        <span class="status-dot status-<?php echo $normalizedStatus == 'on' ? 'on' : 'off'; ?>"></span>
                        <span id="status-<?php echo $device['id']; ?>"><?php echo ucfirst($normalizedStatus); ?></span>
                    </div>

                    <?php if ($device['type'] == 'lamp' && isset($device['brightness'])): ?>
                        <?php if ($isLamp2): ?>
                            <!-- For lamp2, show only the slider control -->
                            <div class="brightness-control">
                                <div class="brightness-display">
                                    <i class="fas fa-sun"></i>
                                    <span id="brightness-value-<?php echo $device['id']; ?>"><?php echo $device['brightness']; ?>%</span>
                                </div>
                                <div class="brightness-slider-container">
                                    <input type="range" id="brightness-<?php echo $device['id']; ?>"
                                        class="brightness-slider" min="0" max="100"
                                        value="<?php echo $device['brightness']; ?>"
                                        data-device-id="<?php echo $device['id']; ?>">
                                </div>
                            </div>
                        <?php else: ?>
                            <!-- For regular lamps, just show the brightness bar -->
                            <div class="device-brightness">
                                <div class="brightness-bar">
                                    <div class="brightness-level" style="width: <?php echo $device['brightness']; ?>%"></div>
                                </div>
                                <span class="brightness-value"><?php echo $device['brightness']; ?>%</span>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>

                <?php if (!$isLamp2): ?>
                    <!-- Only show control buttons for regular devices, not lamp2 -->
                    <div class="device-controls">
                        <button class="btn <?php echo $normalizedStatus == 'on' ? 'btn-off' : 'btn-on'; ?> device-button"
                            data-device-id="<?php echo $device['id']; ?>"
                            data-action="<?php echo $normalizedStatus == 'on' ? 'turn_off' : 'turn_on'; ?>">
                            <i class="fas fa-<?php echo $normalizedStatus == 'on' ? 'power-off' : 'play'; ?>"></i>
                            <?php echo $normalizedStatus == 'on' ? 'Turn Off' : 'Turn On'; ?>
                        </button>
                    </div>
                <?php else: ?>
                    <!-- For lamp2, show a note instead of button -->
                    <div class="device-controls brightness-only">
                        <p class="brightness-note">Adjust the brightness slider to control this lamp</p>
                    </div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- Recent Notifications -->
<div class="card notification-card">
    <div class="card-header-with-actions">
        <h2><i class="fas fa-bell"></i> Recent Notifications</h2>
        <?php if (!empty($notifications)): ?>
            <div class="card-actions">
                <button class="btn btn-sm btn-clear" id="clear-notifications">
                    <i class="fas fa-trash-alt"></i> Clear All
                </button>
            </div>
        <?php endif; ?>
    </div>

    <div id="notifications">
        <?php if (empty($notifications)): ?>
            <div class="notifications-empty">
                <i class="fas fa-check-circle"></i>
                <p>No recent notifications</p>
            </div>
        <?php else: ?>
            <?php foreach ($notifications as $notice): ?>
                <div class="notification <?php echo strpos($notice['type'], 'high') !== false ? 'warning' : 'info'; ?>">
                    <div class="notification-icon">
                        <i class="fas fa-<?php echo strpos($notice['type'], 'high') !== false ? 'exclamation-triangle' : 'info-circle'; ?>"></i>
                    </div>
                    <div class="notification-content">
                        <p class="notification-message">
                            <?php echo htmlspecialchars($notice['message']); ?>
                        </p>
                        <p class="notification-timestamp">
                            <?php echo date('M d, H:i', strtotime($notice['created_at'])); ?>
                        </p>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Refresh all button
        const refreshAllBtn = document.getElementById('refresh-all-btn');
        if (refreshAllBtn) {
            refreshAllBtn.addEventListener('click', function() {
                // Show spinning icon
                this.querySelector('i').classList.add('fa-spin');

                // Get fresh data
                Promise.all([
                    fetch('index.php?action=getDevices', {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    }).then(r => r.json()),
                    fetch('index.php?action=getSensorData', {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    }).then(r => r.json())
                ]).then(([devices, sensorData]) => {
                    // Update device statuses
                    devices.forEach(device => {
                        const statusElement = document.getElementById(`status-${device.id}`);
                        if (statusElement) {
                            statusElement.innerText = device.status === 'on' ? 'On' : 'Off';

                            // Find the device card and update it
                            const deviceCard = statusElement.closest('.device-card');
                            if (deviceCard) {
                                if (device.status === 'on') {
                                    deviceCard.classList.add('device-on');
                                    deviceCard.classList.remove('device-off');
                                } else {
                                    deviceCard.classList.add('device-off');
                                    deviceCard.classList.remove('device-on');
                                }

                                // Update button text and class
                                const button = deviceCard.querySelector('.device-button');
                                if (button) {
                                    if (device.status === 'on') {
                                        button.classList.remove('btn-on');
                                        button.classList.add('btn-off');
                                        button.innerHTML = '<i class="fas fa-power-off"></i> Turn Off';
                                        button.dataset.action = 'turn_off';
                                    } else {
                                        button.classList.remove('btn-off');
                                        button.classList.add('btn-on');
                                        button.innerHTML = '<i class="fas fa-play"></i> Turn On';
                                        button.dataset.action = 'turn_on';
                                    }
                                }
                            }
                        }
                    });

                    // Update sensor data
                    if (sensorData.temperature) {
                        document.getElementById('temperature').innerText = sensorData.temperature;
                    }
                    if (sensorData.humidity) {
                        document.getElementById('humidity').innerText = sensorData.humidity;
                    }

                    // Update last update time
                    document.getElementById('last-update-time').innerText = new Date().toLocaleTimeString();

                    // Stop spinning icon
                    setTimeout(() => {
                        refreshAllBtn.querySelector('i').classList.remove('fa-spin');
                    }, 500);
                }).catch(error => {
                    console.error('Error refreshing data:', error);
                    refreshAllBtn.querySelector('i').classList.remove('fa-spin');
                });
            });
        }

        // Clear notifications button
        const clearNotificationsBtn = document.getElementById('clear-notifications');
        if (clearNotificationsBtn) {
            clearNotificationsBtn.addEventListener('click', function() {
                if (confirm('Are you sure you want to clear all notifications?')) {
                    fetch('index.php?action=clearNotifications', {
                            method: 'POST',
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest'
                            }
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                const notificationsContainer = document.getElementById('notifications');
                                notificationsContainer.innerHTML = `
                            <div class="notifications-empty">
                                <i class="fas fa-check-circle"></i>
                                <p>No recent notifications</p>
                            </div>
                        `;
                            }
                        });
                }
            });
        }

        // Toggle all off button
        const toggleAllOffBtn = document.getElementById('toggle-all-off-btn');
        if (toggleAllOffBtn) {
            toggleAllOffBtn.addEventListener('click', function() {
                if (confirm('Are you sure you want to turn off all devices?')) {
                    fetch('index.php?action=turnOffAll', {
                            method: 'POST',
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest'
                            }
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                // Refresh the page to show updated statuses
                                window.location.reload();
                            }
                        });
                }
            });
        }

        document.querySelectorAll('.brightness-slider').forEach(slider => {
            let brightnessTimer; // For debouncing
            let deviceCard;

            slider.addEventListener('input', function() {
                const deviceId = this.dataset.deviceId;
                const value = this.value;
                deviceCard = this.closest('.device-card');

                // Immediately update the displayed value
                document.querySelectorAll(`#brightness-value-${deviceId}`).forEach(el => {
                    if (el) el.textContent = value + '%';
                });

                // Add subtle visual feedback
                if (deviceCard && !deviceCard.classList.contains('adjusting')) {
                    deviceCard.classList.add('adjusting');
                }

                // Clear any pending timers
                if (brightnessTimer) clearTimeout(brightnessTimer);

                // Debounce the actual API call
                brightnessTimer = setTimeout(() => {
                    updateBrightness(deviceId, value);
                }, 300);
            });

            // Helper function to send brightness updates
            function updateBrightness(deviceId, brightness) {
                // Add visual feedback
                if (deviceCard) deviceCard.classList.add('updating');

                // Send to server
                fetch('index.php?action=setBrightness', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        body: `deviceId=${deviceId}&brightness=${brightness}`
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            showNotification(`Brightness set to ${brightness}%`, 'success');
                        } else {
                            showNotification('Error: ' + (data.message || 'Failed to set brightness'), 'error');
                        }

                        if (deviceCard) {
                            deviceCard.classList.remove('updating');
                            deviceCard.classList.remove('adjusting');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        showNotification('Error occurred while adjusting brightness', 'error');

                        if (deviceCard) {
                            deviceCard.classList.remove('updating');
                            deviceCard.classList.remove('adjusting');
                        }
                    });
            }
        });

        // Add showNotification function if it doesn't exist
        if (typeof showNotification !== 'function') {
            function showNotification(message, type = 'info') {
                const notifications = document.getElementById('notifications');
                if (!notifications) return;

                const notification = document.createElement('div');
                notification.className = `notification ${type}`;

                let iconClass = 'info-circle';
                if (type === 'success') iconClass = 'check-circle';
                if (type === 'error') iconClass = 'exclamation-circle';
                if (type === 'warning') iconClass = 'exclamation-triangle';

                notification.innerHTML = `
                    <div class="notification-icon">
                        <i class="fas fa-${iconClass}"></i>
                    </div>
                    <div class="notification-content">
                        <p class="notification-message">${message}</p>
                        <p class="notification-timestamp">${new Date().toLocaleTimeString()}</p>
                    </div>
                `;

                // Insert at the top
                if (notifications.firstChild) {
                    notifications.insertBefore(notification, notifications.firstChild);
                } else {
                    notifications.appendChild(notification);
                }

                // Auto remove after 5 seconds
                setTimeout(() => {
                    notification.style.opacity = '0';
                    setTimeout(() => {
                        notification.remove();
                    }, 300);
                }, 5000);
            }

            window.showNotification = showNotification;
        }
    });
</script>