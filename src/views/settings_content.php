<?php
require_once dirname(__FILE__) . '/../../src/config/database.php';
require_once dirname(__FILE__) . '/../../src/utils/SystemSettings.php';

// Initialize settings
$settings = SystemSettings::getInstance();
$lastSync = $settings->getLastSync() ?: 'Never';
$hardwareConnected = $settings->isHardwareConnected();

// Handle form submission
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'update_system':
                // Demo action - in reality you'd update actual settings
                $message = 'System settings updated successfully!';
                $messageType = 'success';
                break;
                
            case 'reset_sync':
                // Reset sync timestamp
                $settings->updateLastSync();
                $lastSync = $settings->getLastSync();
                $message = 'Sync timestamp reset successfully!';
                $messageType = 'success';
                break;
                
            case 'hardware_toggle':
                $newStatus = isset($_POST['hardware_status']) && $_POST['hardware_status'] == '1';
                $settings->setHardwareConnected($newStatus);
                $hardwareConnected = $settings->isHardwareConnected();
                $message = 'Hardware connection status updated!';
                $messageType = 'success';
                break;
                
            case 'clear_notifications':
                // Clear notifications
                $database = new Database();
                $db = $database->getConnection();
                $query = "TRUNCATE TABLE notifications";
                $db->exec($query);
                $message = 'All notifications cleared!';
                $messageType = 'success';
                break;
        }
    }
}
?>

<div class="dashboard-header-content">
    <h1><i class="fas fa-cog"></i> Settings</h1>
</div>

<?php if ($message): ?>
<div class="notification <?php echo $messageType; ?> settings-notification">
    <div class="notification-content">
        <p class="notification-message"><?php echo $message; ?></p>
    </div>
</div>
<?php endif; ?>

<div class="settings-grid">
    <!-- System Settings -->
    <div class="card settings-card">
        <h2><i class="fas fa-server"></i> System Settings</h2>
        
        <form method="post" class="settings-form">
            <input type="hidden" name="action" value="update_system">
            
            <div class="form-group">
                <label for="refresh_rate">Auto-refresh Rate (seconds):</label>
                <input type="number" id="refresh_rate" name="refresh_rate" min="5" max="300" value="10" class="form-control">
            </div>
            
            <div class="form-group">
                <label for="language">Language:</label>
                <select id="language" name="language" class="form-control">
                    <option value="en">English</option>
                    <option value="fr">Français</option>
                    <option value="de">Deutsch</option>
                    <option value="es">Español</option>
                </select>
            </div>
            
            <div class="form-group">
                <label for="temperature_unit">Temperature Unit:</label>
                <select id="temperature_unit" name="temperature_unit" class="form-control">
                    <option value="celsius">Celsius (°C)</option>
                    <option value="fahrenheit">Fahrenheit (°F)</option>
                </select>
            </div>
            
            <div class="form-group">
                <button type="submit" class="btn btn-primary">Save System Settings</button>
            </div>
        </form>
    </div>
    
    <!-- Hardware Connection -->
    <div class="card settings-card">
        <h2><i class="fas fa-microchip"></i> Hardware Connection</h2>
        
        <div class="hardware-status">
            <div class="status-indicator-large <?php echo $hardwareConnected ? 'status-on' : 'status-off'; ?>"></div>
            <div class="hardware-status-text">
                <p><strong>Status:</strong> <?php echo $hardwareConnected ? 'Connected' : 'Disconnected'; ?></p>
                <p><strong>Last Sync:</strong> <?php echo $lastSync; ?></p>
            </div>
        </div>
        
        <div class="button-group">
            <form method="post" class="settings-form">
                <input type="hidden" name="action" value="hardware_toggle">
                <input type="hidden" name="hardware_status" value="<?php echo $hardwareConnected ? '0' : '1'; ?>">
                <button type="submit" class="btn <?php echo $hardwareConnected ? 'btn-danger' : 'btn-success'; ?>">
                    <?php echo $hardwareConnected ? 'Disconnect Hardware' : 'Connect Hardware'; ?>
                </button>
            </form>
            
            <form method="post" class="settings-form">
                <input type="hidden" name="action" value="reset_sync">
                <button type="submit" class="btn">Reset Sync Timestamp</button>
            </form>
        </div>
        
        <div class="adafruit-info">
            <h3>Adafruit IO Configuration</h3>
            <div class="form-group">
                <label for="api_key">API Key:</label>
                <div class="input-with-button">
                    <input type="password" id="api_key" value="<?php echo defined('ADAFRUIT_API_KEY') ? substr(ADAFRUIT_API_KEY, 0, 4) . '••••••••••••' : ''; ?>" class="form-control" readonly>
                    <button type="button" class="btn btn-sm toggle-password-visibility" data-target="api_key">
                        <i class="fas fa-eye"></i>
                    </button>
                </div>
            </div>
            
            <div class="form-group">
                <label for="username">Username:</label>
                <input type="text" id="username" value="<?php echo defined('ADAFRUIT_USERNAME') ? ADAFRUIT_USERNAME : ''; ?>" class="form-control" readonly>
            </div>
        </div>
    </div>
    
    <!-- Notification Settings -->
    <div class="card settings-card">
        <h2><i class="fas fa-bell"></i> Notification Settings</h2>
        
        <form method="post" class="settings-form">
            <input type="hidden" name="action" value="update_notifications">
            
            <div class="form-group checkbox-group">
                <label>
                    <input type="checkbox" name="notify_temperature" checked>
                    Temperature alerts
                </label>
            </div>
            
            <div class="form-group">
                <label for="temp_threshold">Temperature Threshold (°C):</label>
                <input type="number" id="temp_threshold" name="temp_threshold" value="30" class="form-control">
            </div>
            
            <div class="form-group checkbox-group">
                <label>
                    <input type="checkbox" name="notify_humidity" checked>
                    Humidity alerts
                </label>
            </div>
            
            <div class="form-group">
                <label for="humidity_threshold">Humidity Threshold (%):</label>
                <input type="number" id="humidity_threshold" name="humidity_threshold" value="70" class="form-control">
            </div>
            
            <div class="form-group checkbox-group">
                <label>
                    <input type="checkbox" name="notify_devices" checked>
                    Device status changes
                </label>
            </div>
            
            <div class="form-group">
                <button type="submit" class="btn btn-primary">Save Notification Settings</button>
            </div>
        </form>
        
        <div class="divider"></div>
        
        <form method="post" class="settings-form" onsubmit="return confirm('Are you sure you want to clear all notifications?');">
            <input type="hidden" name="action" value="clear_notifications">
            <div class="form-group">
                <button type="submit" class="btn btn-danger">
                    <i class="fas fa-trash-alt"></i> Clear All Notifications
                </button>
            </div>
        </form>
    </div>
    
    <!-- User Profile -->
    <div class="card settings-card">
        <h2><i class="fas fa-user-circle"></i> User Profile</h2>
        
        <form method="post" class="settings-form">
            <input type="hidden" name="action" value="update_profile">
            
            <div class="form-group">
                <label for="fullname">Full Name:</label>
                <input type="text" id="fullname" name="fullname" value="Admin User" class="form-control">
            </div>
            
            <div class="form-group">
                <label for="email">Email:</label>
                <input type="email" id="email" name="email" value="admin@example.com" class="form-control">
            </div>
            
            <div class="form-group">
                <label for="current_password">Current Password:</label>
                <input type="password" id="current_password" name="current_password" class="form-control">
            </div>
            
            <div class="form-group">
                <label for="new_password">New Password:</label>
                <input type="password" id="new_password" name="new_password" class="form-control">
            </div>
            
            <div class="form-group">
                <label for="confirm_password">Confirm New Password:</label>
                <input type="password" id="confirm_password" name="confirm_password" class="form-control">
            </div>
            
            <div class="form-group">
                <button type="submit" class="btn btn-primary">Update Profile</button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Password visibility toggle
    const toggleButtons = document.querySelectorAll('.toggle-password-visibility');
    toggleButtons.forEach(button => {
        button.addEventListener('click', function() {
            const targetId = this.dataset.target;
            const inputField = document.getElementById(targetId);
            
            if (inputField.type === 'password') {
                inputField.type = 'text';
                this.querySelector('i').classList.replace('fa-eye', 'fa-eye-slash');
            } else {
                inputField.type = 'password';
                this.querySelector('i').classList.replace('fa-eye-slash', 'fa-eye');
            }
        });
    });
});
</script>

<style>
/* Settings specific styles */
.settings-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
    gap: var(--spacing-lg);
}

.settings-card {
    display: flex;
    flex-direction: column;
}

.settings-form {
    margin-bottom: var(--spacing-md);
}

.form-group {
    margin-bottom: var(--spacing-md);
}

.form-group label {
    display: block;
    margin-bottom: var(--spacing-xs);
    font-weight: 500;
}

.form-control {
    width: 100%;
    padding: 0.5rem;
    border: 1px solid var(--border-color);
    border-radius: var(--border-radius-sm);
    font-size: 0.9rem;
}

.checkbox-group {
    display: flex;
    align-items: center;
}

.checkbox-group label {
    display: flex;
    align-items: center;
    cursor: pointer;
    margin-bottom: 0;
}

.checkbox-group input[type="checkbox"] {
    margin-right: var(--spacing-sm);
}

.button-group {
    display: flex;
    gap: var(--spacing-sm);
    flex-wrap: wrap;
    margin: var(--spacing-md) 0;
}

.settings-notification {
    margin-bottom: var(--spacing-lg);
}

.hardware-status {
    display: flex;
    align-items: center;
    margin-bottom: var(--spacing-md);
}

.status-indicator-large {
    width: 16px;
    height: 16px;
    border-radius: 50%;
    margin-right: var(--spacing-md);
}

.hardware-status-text p {
    margin: var(--spacing-xs) 0;
}

.divider {
    height: 1px;
    background-color: var(--border-color);
    margin: var(--spacing-md) 0;
}

.about-info p {
    margin-bottom: var(--spacing-xs);
}

.input-with-button {
    display: flex;
}

.input-with-button .form-control {
    border-top-right-radius: 0;
    border-bottom-right-radius: 0;
    flex-grow: 1;
}

.input-with-button .btn {
    border-top-left-radius: 0;
    border-bottom-left-radius: 0;
}

.adafruit-info {
    margin-top: var(--spacing-md);
    padding-top: var(--spacing-md);
    border-top: 1px solid var(--border-color);
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .settings-grid {
        grid-template-columns: 1fr;
    }
    
    .button-group {
        flex-direction: column;
    }
    
    .button-group .btn {
        width: 100%;
    }
}
</style>