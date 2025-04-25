document.addEventListener('DOMContentLoaded', function() {
    // Sidebar toggle
    const sidebarToggleBtn = document.getElementById('sidebar-toggle-btn');
    const mobileSidebarToggle = document.getElementById('mobile-sidebar-toggle');
    const appContainer = document.querySelector('.app-container');
    
    // Desktop sidebar toggle with icon rotation
    if (sidebarToggleBtn) {
        sidebarToggleBtn.addEventListener('click', function() {
            appContainer.classList.toggle('sidebar-collapsed');
            
            // Update toggle icon direction
            const icon = this.querySelector('i');
            if (appContainer.classList.contains('sidebar-collapsed')) {
                icon.classList.remove('fa-chevron-left');
                icon.classList.add('fa-chevron-right');
            } else {
                icon.classList.remove('fa-chevron-right');
                icon.classList.add('fa-chevron-left');
            }
            
            // Save preference to localStorage
            const isCollapsed = appContainer.classList.contains('sidebar-collapsed');
            localStorage.setItem('sidebar-collapsed', isCollapsed);
        });
    }
    
    // Mobile sidebar toggle
    if (mobileSidebarToggle) {
        mobileSidebarToggle.addEventListener('click', function() {
            appContainer.classList.toggle('sidebar-mobile-open');
            
            // Close sidebar when clicking outside
            document.addEventListener('click', function closeOutside(event) {
                if (!event.target.closest('.sidebar') && 
                    !event.target.closest('#mobile-sidebar-toggle') &&
                    !event.target.closest('#sidebar-toggle-btn') &&
                    appContainer.classList.contains('sidebar-mobile-open')) {
                    appContainer.classList.remove('sidebar-mobile-open');
                    document.removeEventListener('click', closeOutside);
                }
            });
        });
    }
    
    // Load sidebar collapse preference and update toggle icon
    const savedCollapse = localStorage.getItem('sidebar-collapsed');
    if (savedCollapse === 'true' && sidebarToggleBtn) {
        appContainer.classList.add('sidebar-collapsed');
        const icon = sidebarToggleBtn.querySelector('i');
        if (icon) {
            icon.classList.remove('fa-chevron-left');
            icon.classList.add('fa-chevron-right');
        }
    }
    
    // Notification dropdown
    const notificationToggle = document.querySelector('.notification-toggle');
    const notificationDropdown = document.querySelector('.notification-dropdown');
    
    if (notificationToggle && notificationDropdown) {
        notificationToggle.addEventListener('click', function(e) {
            e.stopPropagation();
            notificationDropdown.classList.toggle('active');
            
            // Close profile dropdown if open
            if (profileDropdown && profileDropdown.classList.contains('active')) {
                profileDropdown.classList.remove('active');
            }
        });
    }
    
    // Profile dropdown
    const profileToggle = document.querySelector('.profile-toggle');
    const profileDropdown = document.querySelector('.profile-dropdown');
    
    if (profileToggle && profileDropdown) {
        profileToggle.addEventListener('click', function(e) {
            e.stopPropagation();
            profileDropdown.classList.toggle('active');
            
            // Close notification dropdown if open
            if (notificationDropdown && notificationDropdown.classList.contains('active')) {
                notificationDropdown.classList.remove('active');
            }
        });
    }
    
    // Close dropdowns when clicking elsewhere
    document.addEventListener('click', function(e) {
        if (notificationDropdown && !e.target.closest('.header-notification')) {
            notificationDropdown.classList.remove('active');
        }
        
        if (profileDropdown && !e.target.closest('.header-profile')) {
            profileDropdown.classList.remove('active');
        }
    });
    
    // All Off button functionality
    const allOffBtn = document.getElementById('toggle-all-off');
    if (allOffBtn) {
        allOffBtn.addEventListener('click', function() {
            if (confirm('Turn off all devices?')) {
                fetch('../ajax/turn_all_off.php')
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            // Refresh the page or update device statuses
                            window.location.reload();
                        } else {
                            alert('Failed to turn off all devices');
                        }
                    })
                    .catch(error => console.error('Error:', error));
            }
        });
    }
});