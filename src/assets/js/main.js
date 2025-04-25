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
            const status = action === 'turn_on' ? 'on' : 'off';
            
            // Store original button text
            const originalText = this.innerHTML;
            
            // Show loading state
            this.disabled = true;
            this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
            
            console.log(`Toggling device ${deviceId} to ${status}`);

            // Use correct path
            fetch('index.php?action=toggleDevice', { // Changed this line
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: `deviceId=${deviceId}&status=${status}`
            })
            .then(response => {
                console.log("Response received:", response);
                return response.json();
            })
            .then(data => {
                console.log('Device toggle response:', data);
                if (data.success) {
                    // Update UI to reflect the new state
                    updateDeviceStatus(deviceId, status);
                    
                    // Show success notification
                    showNotification(`${data.device || 'Device'} turned ${status}`, 'success');
                } else {
                    console.error('Error toggling device:', data);
                    alert('Error: ' + (data.message || 'Failed to toggle device'));
                    
                    // Reset button to original state
                    this.innerHTML = originalText;
                }
            })
            .catch(error => {
                //console.error('Error:', error);
                //alert('An error occurred while controlling the device');
                this.innerHTML = originalText;
            })
            .finally(() => {
                // Re-enable button
                this.disabled = false;
            });
        });
    });

    function updateDeviceStatus(deviceId, status) {
        console.log(`Updating UI for device ${deviceId} to ${status}`);
        
        // Normalize status value (might be 0/1 or on/off)
        let normalizedStatus;
        if (status === '1' || status === 1 || status === 'on') {
            normalizedStatus = 'on';
        } else if (status === '0' || status === 0 || status === 'off') {
            normalizedStatus = 'off';
        } else {
            normalizedStatus = status; // Keep as is if it's something unexpected
        }
        
        const statusElement = document.querySelector(`#status-${deviceId}`);
        const buttonElement = document.querySelector(`[data-device-id="${deviceId}"]`);
        let deviceCard = null;
        
        // Find the device card (more compatible approach)
        if (buttonElement) {
            deviceCard = buttonElement.closest('.device-card');
        }
        
        if (statusElement) {
            statusElement.textContent = normalizedStatus.charAt(0).toUpperCase() + normalizedStatus.slice(1);
        }
        
        if (deviceCard) {
            if (normalizedStatus === 'on') {
                deviceCard.classList.add('device-on');
                deviceCard.classList.remove('device-off');
            } else {
                deviceCard.classList.remove('device-on');
                deviceCard.classList.add('device-off');
            }
        }
        
        if (buttonElement) {
            if (normalizedStatus === 'on') {
                buttonElement.textContent = 'Turn Off';
                buttonElement.dataset.action = 'turn_off';
                buttonElement.classList.remove('btn-on');
                buttonElement.classList.add('btn-off');
            } else {
                buttonElement.textContent = 'Turn On';
                buttonElement.dataset.action = 'turn_on';
                buttonElement.classList.remove('btn-off');
                buttonElement.classList.add('btn-on');
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
                // Normalize status before updating UI
                let status;
                if (device.status === '1' || device.status === 1 || device.status === 'on') {
                    status = 'on';
                } else if (device.status === '0' || device.status === 0 || device.status === 'off') {
                    status = 'off';
                } else {
                    status = device.status;
                }
                
                // Now update UI with normalized status
                updateDeviceStatus(device.id, status);
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