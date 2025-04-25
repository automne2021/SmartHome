class AdafruitAutoSync {
    constructor(options = {}) {
        this.syncInterval = options.syncInterval || 30000; // Default: sync every 30 seconds
        this.lastSyncTime = 0;
        this.isRunning = false;
        this.syncEndpoint = options.syncEndpoint || 'ajax_sync.php';
        this.onUpdate = options.onUpdate || function() {};
    }
    
    start() {
        if (this.isRunning) return;
        
        this.isRunning = true;
        this.performSync(); // Initial sync
        
        // Set interval for recurring syncs
        this.intervalId = setInterval(() => {
            this.performSync();
        }, this.syncInterval);
        
        console.log('Auto-sync started. Interval:', this.syncInterval + 'ms');
    }
    
    stop() {
        if (!this.isRunning) return;
        
        clearInterval(this.intervalId);
        this.isRunning = false;
        console.log('Auto-sync stopped');
    }
    
    async performSync() {
        try {
            const startTime = Date.now();
            console.log('Syncing with Adafruit...');
            
            const response = await fetch(this.syncEndpoint);
            if (!response.ok) {
                throw new Error(`HTTP error: ${response.status}`);
            }
            
            const data = await response.json();
            this.lastSyncTime = Date.now();
            
            console.log('Sync completed in', (this.lastSyncTime - startTime) + 'ms');
            console.log('Result:', data);
            
            // Call the onUpdate callback with the sync result
            this.onUpdate(data);
            
            return data;
        } catch (error) {
            console.error('Sync error:', error);
            return { success: false, error: error.message };
        }
    }
    
    getStatus() {
        return {
            isRunning: this.isRunning,
            lastSync: this.lastSyncTime ? new Date(this.lastSyncTime).toLocaleString() : 'Never',
            interval: this.syncInterval
        };
    }
}