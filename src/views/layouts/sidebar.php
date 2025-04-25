<div class="sidebar">
    <div class="sidebar-header">
        <h3><i class="fas fa-home"></i> <span>SmartHome</span></h3>
    </div>

    <div class="sidebar-user">
        <div class="user-avatar">
            <i class="fas fa-user-circle"></i>
        </div>
        <div class="user-info">
            <p class="user-name">Admin User</p>
            <p class="user-role">Administrator</p>
        </div>
    </div>

    <ul class="sidebar-menu">
        <li class="<?php echo $page === 'dashboard' ? 'active' : ''; ?>">
            <a href="index.php">
                <i class="fas fa-tachometer-alt"></i>
                <span>Dashboard</span>
            </a>
        </li>
        <li class="<?php echo $page === 'devices' ? 'active' : ''; ?>">
            <a href="index.php?action=devices">
                <i class="fas fa-plug"></i>
                <span>Devices</span>
            </a>
        </li>
        <li class="<?php echo $page === 'analytics' ? 'active' : ''; ?>">
            <a href="index.php?action=analytics">
                <i class="fas fa-chart-line"></i>
                <span>Analytics</span>
            </a>
        </li>
        <li class="<?php echo $page === 'settings' ? 'active' : ''; ?>">
            <a href="index.php?action=settings">
                <i class="fas fa-cog"></i>
                <span>Settings</span>
            </a>
        </li>
    </ul>

    <div class="sidebar-footer">
        <p>&copy; <?php echo date('Y'); ?> SmartHome</p>
        <p>v1.0.0</p>
    </div>
</div>

<button id="sidebar-toggle-btn" class="sidebar-float-toggle">
    <i class="fas fa-chevron-left"></i>
</button>