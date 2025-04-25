<?php
// Set page title and CSS
$pageTitle = "Manage Devices";
$additionalCss = ["devices-enhanced"];

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
    <h1><i class="fas fa-plug"></i> Manage Devices</h1>
</div>

<div class="card">
    <div class="card-header-with-actions">
        <div class="device-types">
            <div class="device-type-tab active" data-type="all">
                <i class="fas fa-border-all"></i> All Devices
            </div>
            <?php foreach (array_keys($devicesByType) as $type): ?>
                <div class="device-type-tab" data-type="<?php echo $type; ?>">
                    <?php
                    $icon = 'question';
                    switch ($type) {
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
                    <i class="fas fa-<?php echo $icon; ?>"></i>
                    <?php echo ucfirst($type); ?>s
                </div>
            <?php endforeach; ?>
        </div>

        <div class="card-actions">
            <button class="btn btn-sm" id="toggle-all">
                <i class="fas fa-power-off"></i> Toggle All
            </button>
            <button class="btn btn-sm" id="refresh-devices">
                <i class="fas fa-sync-alt"></i> Refresh
            </button>
        </div>
    </div>

    <!-- All devices section -->
    <div class="device-type-section active" id="all">
        <div class="devices-grid">
            <?php echo renderDeviceCards($devices); ?>
        </div>
    </div>

    <!-- Device type sections -->
    <?php foreach ($devicesByType as $type => $typeDevices): ?>
        <div class="device-type-section" id="<?php echo $type; ?>">
            <div class="devices-grid">
                <?php echo renderDeviceCards($typeDevices); ?>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<?php
/**
 * Helper function to render device cards
 */
function renderDeviceCards($devices)
{
    $output = '';

    foreach ($devices as $device) {
        // Normalize the device status here
        $normalizedStatus = normalizeDeviceStatus($device['status']);

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

        $output .= '<div class="device-card ' . ($normalizedStatus == 'on' ? 'device-on' : 'device-off') . '" data-device-id="' . $device['id'] . '">';

        // Status badge
        $output .= '<div class="device-status-badge"><span>' . ucfirst($normalizedStatus) . '</span></div>';

        // Type badge
        $output .= '<div class="device-type-badge">';
        $output .= '<i class="fas fa-' . $icon . '"></i>';
        $output .= '<span>' . ucfirst($device['type']) . '</span>';
        $output .= '</div>';

        // Device icon
        $output .= '<div class="device-icon">';
        $output .= '<i class="fas fa-' . $icon . '"></i>';
        $output .= '</div>';

        // Device info
        $output .= '<div class="device-info">';
        $output .= '<h3>' . htmlspecialchars($device['name']) . '</h3>';
        $output .= '<div class="device-status-indicator">';
        $output .= '<span class="status-dot status-' . ($normalizedStatus == 'on' ? 'on' : 'off') . '"></span>';
        $output .= '<span id="status-' . $device['id'] . '" class="status-text">' . ucfirst($normalizedStatus) . '</span>';
        $output .= '</div>';
        $output .= '</div>';

        // Check if this is the special lamp2 with brightness
        $isLamp2 = ($device['type'] == 'lamp' && $device['adafruit_feed'] == 'lamp2');

        // Brightness control for lamps
        if ($device['type'] == 'lamp' && isset($device['brightness'])) {
            $output .= '<div class="brightness-control">';
            $output .= '<div class="brightness-display">';
            $output .= '<i class="fas fa-sun"></i>';
            $output .= '<span id="brightness-value-' . $device['id'] . '">' . $device['brightness'] . '%</span>';
            $output .= '</div>';
            $output .= '<div class="brightness-slider-container">';
            $output .= '<input type="range" id="brightness-' . $device['id'] . '" class="brightness-slider" min="0" max="100" value="' . $device['brightness'] . '" data-device-id="' . $device['id'] . '">';
            $output .= '</div>';
            $output .= '</div>';
        }

        // Device actions - only show for regular devices, not lamp2
        if (!$isLamp2) {
            $output .= '<div class="device-actions">';
            $output .= '<button class="btn ' . ($normalizedStatus == 'on' ? 'btn-off' : 'btn-on') . ' device-button" ';
            $output .= 'data-device-id="' . $device['id'] . '" ';
            $output .= 'data-action="' . ($normalizedStatus == 'on' ? 'turn_off' : 'turn_on') . '">';
            $output .= '<i class="fas fa-' . ($normalizedStatus == 'on' ? 'power-off' : 'play') . '"></i> ';
            $output .= ($normalizedStatus == 'on' ? 'Turn Off' : 'Turn On');
            $output .= '</button>';
            $output .= '</div>';
        } else {
            // For lamp2, just show a small instruction
            $output .= '<div class="device-actions brightness-only">';
            $output .= '<p class="brightness-note">Adjust the brightness slider to control this lamp</p>';
            $output .= '</div>';
        }

        $output .= '</div>'; // Close device card
    }

    return $output;
}
?>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Tab functionality
        document.querySelectorAll('.device-type-tab').forEach(tab => {
            tab.addEventListener('click', function() {
                // Remove active class from all tabs
                document.querySelectorAll('.device-type-tab').forEach(t => {
                    t.classList.remove('active');
                });

                // Add active class to clicked tab
                this.classList.add('active');

                // Hide all sections
                document.querySelectorAll('.device-type-section').forEach(section => {
                    section.classList.remove('active');
                });

                // Show selected section
                const type = this.dataset.type;
                document.getElementById(type).classList.add('active');
            });
        });

        // Brightness slider functionality
        document.querySelectorAll('.brightness-slider').forEach(slider => {
            slider.addEventListener('input', function() {
                const deviceId = this.dataset.deviceId;
                const value = this.value;

                // Update displayed value
                document.querySelectorAll(`#brightness-value-${deviceId}`).forEach(el => {
                    if (el) el.textContent = value + '%';
                });
            });

            slider.addEventListener('change', function() {
                const deviceId = this.dataset.deviceId;
                const brightness = this.value;

                // Find device card
                const deviceCard = this.closest('.device-card');
                deviceCard.classList.add('updating');

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
                        deviceCard.classList.remove('updating');

                        if (data.success) {
                            showDeviceToast('Brightness updated', `Brightness set to ${brightness}%`, 'success');
                        } else {
                            showDeviceToast('Error', data.message || 'Failed to update brightness', 'error');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        deviceCard.classList.remove('updating');
                        showDeviceToast('Error', 'Failed to connect to server', 'error');
                    });
            });
        });

        // Toggle all devices button
        const toggleAllBtn = document.getElementById('toggle-all');
        if (toggleAllBtn) {
            toggleAllBtn.addEventListener('click', function() {
                if (confirm('Are you sure you want to toggle all devices?')) {
                    // Count active devices to determine action
                    const activeDevices = document.querySelectorAll('.device-card.device-on').length;
                    const action = activeDevices > 0 ? 'turnOffAll' : 'turnOnAll';

                    // Show loading state
                    this.disabled = true;
                    this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';

                    // Send request to server
                    fetch(`index.php?action=${action}`, {
                            method: 'POST',
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest'
                            }
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                // Show success message
                                showDeviceToast(
                                    'Success',
                                    activeDevices > 0 ? 'All devices turned off' : 'All devices turned on',
                                    'success'
                                );

                                // Refresh after delay
                                setTimeout(() => {
                                    window.location.reload();
                                }, 1000);
                            } else {
                                // Reset button
                                this.disabled = false;
                                this.innerHTML = '<i class="fas fa-power-off"></i> Toggle All';

                                showDeviceToast('Error', data.message || 'Failed to update devices', 'error');
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);

                            // Reset button
                            this.disabled = false;
                            this.innerHTML = '<i class="fas fa-power-off"></i> Toggle All';

                            showDeviceToast('Error', 'Failed to connect to server', 'error');
                        });
                }
            });
        }

        // Refresh devices button
        const refreshBtn = document.getElementById('refresh-devices');
        if (refreshBtn) {
            refreshBtn.addEventListener('click', function() {
                // Show loading state
                this.disabled = true;
                this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Refreshing...';

                // Fetch updated device data
                fetch('index.php?action=getDevices', {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    })
                    .then(response => response.json())
                    .then(devices => {
                        // Reset button
                        this.disabled = false;
                        this.innerHTML = '<i class="fas fa-sync-alt"></i> Refresh';

                        // Reload the page to show fresh data
                        window.location.reload();
                    })
                    .catch(error => {
                        console.error('Error:', error);

                        // Reset button
                        this.disabled = false;
                        this.innerHTML = '<i class="fas fa-sync-alt"></i> Refresh';

                        showDeviceToast('Error', 'Failed to refresh devices', 'error');
                    });
            });
        }

        // Device buttons functionality
        document.querySelectorAll('.device-button').forEach(button => {
            button.addEventListener('click', function() {
                const deviceId = this.dataset.deviceId;
                const action = this.dataset.action;

                // Show loading state
                this.disabled = true;
                const originalHtml = this.innerHTML;
                this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';

                // Find device card
                const deviceCard = this.closest('.device-card');
                deviceCard.classList.add('updating');

                // Send to server
                fetch('index.php?action=toggleDevice', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        body: `deviceId=${deviceId}`
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            const newStatus = data.newStatus || (action === 'turn_on' ? 'on' : 'off');
                            const isOn = newStatus === 'on';
                            const statusText = isOn ? 'On' : 'Off';

                            // Refresh the page to show updated status across all tabs
                            window.location.reload();

                            // Show success toast
                            const deviceName = deviceCard.querySelector('h3').innerText;
                            showDeviceToast(
                                'Success',
                                `${deviceName} turned ${statusText.toLowerCase()}`,
                                'success'
                            );
                        } else {
                            // Reset button to original state
                            this.disabled = false;
                            this.innerHTML = originalHtml;

                            // Remove updating class
                            deviceCard.classList.remove('updating');

                            // Show error toast
                            showDeviceToast('Error', data.message || 'Failed to update device', 'error');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);

                        // Reset button
                        this.disabled = false;
                        this.innerHTML = originalHtml;

                        // Remove updating class
                        deviceCard.classList.remove('updating');

                        showDeviceToast('Error', 'Failed to connect to server', 'error');
                    });
            });
        });

        // Function to show a toast notification
        function showDeviceToast(title, message, type = 'info') {
            // Remove any existing toasts
            const existingToasts = document.querySelectorAll('.device-toast');
            existingToasts.forEach(toast => {
                toast.remove();
            });

            // Create new toast
            const toast = document.createElement('div');
            toast.className = `device-toast ${type}`;

            let icon = 'info-circle';
            if (type === 'success') icon = 'check-circle';
            if (type === 'error') icon = 'exclamation-circle';
            if (type === 'warning') icon = 'exclamation-triangle';

            toast.innerHTML = `
            <div class="toast-icon">
                <i class="fas fa-${icon}"></i>
            </div>
            <div class="toast-content">
                <h4>${title}</h4>
                <p>${message}</p>
            </div>
            <button class="toast-close" aria-label="Close">
                <i class="fas fa-times"></i>
            </button>
        `;

            document.body.appendChild(toast);

            // Add event listener for close button
            toast.querySelector('.toast-close').addEventListener('click', () => {
                toast.style.opacity = '0';
                setTimeout(() => {
                    toast.remove();
                }, 300);
            });

            // Auto-remove after 4 seconds
            setTimeout(() => {
                toast.style.opacity = '0';
                setTimeout(() => {
                    toast.remove();
                }, 300);
            }, 4000);
        }
    });
</script>