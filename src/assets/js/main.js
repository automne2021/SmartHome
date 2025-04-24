document.addEventListener('DOMContentLoaded', function() {
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
                statusElement.textContent = 'On';
                if (buttonElement) {
                    buttonElement.textContent = 'Turn Off';
                    buttonElement.dataset.action = 'turn_off';
                }
            } else {
                statusElement.textContent = 'Off';
                if (buttonElement) {
                    buttonElement.textContent = 'Turn On';
                    buttonElement.dataset.action = 'turn_on';
                }
            }
        }
    }

    // Fetch sensor data periodically
    fetchSensorData();
    setInterval(fetchSensorData, 10000);

    function fetchSensorData() {
        fetch('index.php?action=getSensorData')
        .then(response => response.json())
        .then(data => {
            document.querySelector('#temperature').textContent = data.temperature + ' °C';
            document.querySelector('#humidity').textContent = data.humidity + ' %';
        })
        .catch(error => console.error('Error fetching sensor data:', error));
    }
});