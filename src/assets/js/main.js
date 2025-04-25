document.addEventListener('DOMContentLoaded', function() {
    // Initialize auto-sync
    if (typeof AdafruitAutoSync !== 'undefined') {
        const autoSync = new AdafruitAutoSync({
            syncInterval: 10000, // Sync every 1 minute
            onUpdate: function(data) {
                // Update UI with new data
                if (data.success) {
                    fetchSensorData(); // Refresh sensor data display
                    fetchDevices();    // Refresh device status display
                    
                    // Show sync notification
                    showNotification('Data synchronized successfully', 'success');
                } else {
                    showNotification('Sync failed: ' + (data.error || 'Unknown error'), 'error');
                }
            }
        });
        
        // Start the auto-sync
        autoSync.start();
        
        // Show sync status
        const syncStatus = document.getElementById('sync-status');
        if (syncStatus) {
            syncStatus.textContent = 'Auto-sync enabled';
        }
        
        // Allow manual refresh
        const refreshButton = document.getElementById('refresh-data');
        if (refreshButton) {
            refreshButton.addEventListener('click', function() {
                autoSync.performSync();
                this.disabled = true;
                setTimeout(() => { this.disabled = false; }, 3000); // Prevent button spam
            });
        }
    }
    
    // Set up device control buttons
    const deviceButtons = document.querySelectorAll('.device-button');
    
    deviceButtons.forEach(button => {
        button.addEventListener('click', function() {
            const deviceId = this.dataset.deviceId;
            const action = this.dataset.action;
            const status = action === 'turn_on' ? 1 : 0;

            fetch('index.php?action=toggleDevice', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `deviceId=${deviceId}&status=${status}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    updateDeviceStatus(deviceId, status);
                } else {
                    alert('Error: ' + (data.message || 'Failed to toggle device'));
                }
            })
            .catch(error => console.error('Error:', error));
        });
    });

    function updateDeviceStatus(deviceId, status) {
        const statusElement = document.querySelector(`#status-${deviceId}`);
        const buttonElement = document.querySelector(`[data-device-id="${deviceId}"]`);
        
        if (statusElement) {
            if (status == 1) {
                statusElement.textContent = 'on';
                if (buttonElement) {
                    buttonElement.textContent = 'Turn Off';
                    buttonElement.dataset.action = 'turn_off';
                }
            } else {
                statusElement.textContent = 'off';
                if (buttonElement) {
                    buttonElement.textContent = 'Turn On';
                    buttonElement.dataset.action = 'turn_on';
                }
            }
        }
    }

    // Fetch sensor data periodically (backup for auto-sync)
    fetchSensorData();
    
    function fetchSensorData() {
        fetch('index.php?action=getSensorData')
        .then(response => response.json())
        .then(data => {
            const tempElement = document.querySelector('#temperature');
            const humidityElement = document.querySelector('#humidity');
            
            if (tempElement) tempElement.textContent = data.temperature;
            if (humidityElement) humidityElement.textContent = data.humidity;
            
            // Check for alerts (if temperature or humidity is too high)
            checkAlerts(data);
        })
        .catch(error => console.error('Error fetching sensor data:', error));
    }
    
    function fetchDevices() {
        fetch('index.php?action=getDevices')
        .then(response => response.json())
        .then(devices => {
            devices.forEach(device => {
                updateDeviceStatus(device.id, device.status === 'on' ? 1 : 0);
            });
        })
        .catch(error => console.error('Error fetching devices:', error));
    }
    
    function checkAlerts(data) {
        // Check temperature threshold
        if (data.temperature > 30) {
            showNotification(`High temperature detected: ${data.temperature}°C`, 'warning');
        }
        
        // Check humidity threshold
        if (data.humidity > 70) {
            showNotification(`High humidity detected: ${data.humidity}%`, 'warning');
        }
    }
    
    function showNotification(message, type = 'info') {
        const notifications = document.getElementById('notifications');
        if (!notifications) return;
        
        const notification = document.createElement('div');
        notification.className = `notification ${type}`;
        notification.innerHTML = `
            <span>${message}</span>
            <button class="close-btn">&times;</button>
        `;
        
        // Add close button functionality
        const closeBtn = notification.querySelector('.close-btn');
        closeBtn.addEventListener('click', () => {
            notification.remove();
        });
        
        // Auto remove after 10 seconds
        setTimeout(() => {
            notification.classList.add('fade-out');
            setTimeout(() => notification.remove(), 500);
        }, 10000);
        
        // Add to notifications container
        notifications.appendChild(notification);
    }
});