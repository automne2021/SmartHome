<header class="main-header">
    <div class="header-left">
        <button id="mobile-sidebar-toggle" class="btn-icon d-md-none">
            <i class="fas fa-bars"></i>
        </button>
    </div>

    <div class="header-center">
        <div class="quick-actions">
            <button class="btn btn-sm" id="refresh-data">
                <i class="fas fa-sync-alt"></i> Refresh
            </button>
            <button class="btn btn-sm" id="toggle-all-off">
                <i class="fas fa-power-off"></i> All Off
            </button>
            <div class="sync-info">
                <i class="fas fa-sync"></i> Last sync:
                <span id="last-sync"><?php echo $settings->getLastSync() ?: 'Never'; ?></span>
            </div>
        </div>
    </div>

    <div class="header-right">
        <div class="header-notification">
            <button class="btn-icon notification-toggle">
                <i class="fas fa-bell"></i>
                <?php if (!empty($alerts)): ?>
                    <span class="notification-badge"><?php echo count($alerts); ?></span>
                <?php endif; ?>
            </button>
            <div class="notification-dropdown">
                <div class="notification-header">
                    <h4>Notifications</h4>
                    <a href="index.php?action=notifications">See all</a>
                </div>
                <div class="notification-list">
                    <?php if (empty($notifications)): ?>
                        <div class="no-notifications">No new notifications</div>
                    <?php else: ?>
                        <?php foreach (array_slice($notifications, 0, 3) as $notice): ?>
                            <div class="notification-item <?php echo strpos($notice['type'], 'high') !== false ? 'warning' : 'info'; ?>">
                                <div class="notification-content">
                                    <p><?php echo htmlspecialchars($notice['message']); ?></p>
                                    <small><?php echo date('M d, H:i', strtotime($notice['created_at'])); ?></small>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="header-profile">
            <button class="btn-icon profile-toggle">
                <i class="fas fa-user-circle"></i>
            </button>
            <div class="profile-dropdown">
                <a href="index.php?action=profile">
                    <i class="fas fa-user"></i> Profile
                </a>
                <a href="index.php?action=settings">
                    <i class="fas fa-cog"></i> Settings
                </a>
                <a href="logout.php">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </div>
    </div>
</header>