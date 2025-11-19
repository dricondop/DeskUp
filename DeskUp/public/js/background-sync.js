class BackgroundSyncService {
    constructor() {
        this.syncInterval = 10000; // Sync every 10 seconds
        this.statusCheckInterval = 5000; // Check API status every 5 seconds
        this.isApiAvailable = false;
        this.isSyncing = false;
        this.syncTimer = null;
        this.statusTimer = null;
        this.lastSyncTime = null;
        this.syncCount = 0;
    }

    /**
     * Initialize the background sync service
     */
    init() {
        console.log('[BackgroundSync] Service initialized');
        
        // Start checking API status
        this.startStatusCheck();
        
        // Start automatic syncing
        this.startAutoSync();
        
        // Update UI indicators
        this.updateStatusIndicator();
    }

    /**
     * Check API availability periodically
     */
    async checkAPIStatus() {
        try {
            const response = await fetch('/api/desks/status', {
                method: 'GET',
                headers: {
                    'Accept': 'application/json'
                }
            });
            
            if (response.ok) {
                const data = await response.json();
                this.isApiAvailable = data.api_available;
            } else {
                this.isApiAvailable = false;
            }
        } catch (error) {
            console.error('[BackgroundSync] Status check failed:', error);
            this.isApiAvailable = false;
        }
        
        this.updateStatusIndicator();
    }

    /**
     * Start periodic API status checks
     */
    startStatusCheck() {
        // Check immediately
        this.checkAPIStatus();
        
        // Then check periodically
        this.statusTimer = setInterval(() => {
            this.checkAPIStatus();
        }, this.statusCheckInterval);
    }

    /**
     * Perform desk synchronization from API
     */
    async performSync() {
        // Skip if already syncing or API is not available
        if (this.isSyncing || !this.isApiAvailable) {
            return;
        }

        this.isSyncing = true;
        console.log('[BackgroundSync] Starting sync...');

        try {
            const response = await fetch('/api/desks/sync', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                }
            });

            if (response.ok) {
                const data = await response.json();
                
                if (data.success) {
                    this.lastSyncTime = new Date();
                    this.syncCount++;
                    
                    console.log('[BackgroundSync] Sync completed:', {
                        created: data.results.created,
                        updated: data.results.updated,
                        total: data.results.total_api_desks,
                        errors: data.results.errors.length
                    });

                    // Reload layout if there were changes
                    if (data.results.created > 0 || data.results.updated > 0) {
                        console.log('[BackgroundSync] Changes detected, reloading layout...');
                        await this.reloadLayout();
                    }
                } else {
                    console.error('[BackgroundSync] Sync failed:', data.error);
                }
            } else {
                console.error('[BackgroundSync] Sync request failed:', response.status);
            }
        } catch (error) {
            console.error('[BackgroundSync] Sync error:', error);
        } finally {
            this.isSyncing = false;
        }
    }

    /**
     * Reload layout without full page refresh
     */
    async reloadLayout() {
        try {
            const response = await fetch('/layout/load', {
                method: 'GET',
                headers: {
                    'Accept': 'application/json'
                }
            });

            if (response.ok) {
                const data = await response.json();
                
                if (data.desks && data.desks.length > 0) {
                    // Clear existing desks
                    document.querySelectorAll('.desk').forEach(desk => desk.remove());
                    
                    // Add updated desks
                    data.desks.forEach(deskData => {
                        if (typeof window.addDesk === 'function') {
                            window.addDesk(deskData.x, deskData.y, deskData.name, deskData.id);
                        }
                    });
                    
                    console.log('[BackgroundSync] Layout reloaded with', data.desks.length, 'desks');
                }
            }
        } catch (error) {
            console.error('[BackgroundSync] Layout reload failed:', error);
        }
    }

    /**
     * Start automatic periodic syncing
     */
    startAutoSync() {
        // Perform initial sync after a short delay
        setTimeout(() => {
            this.performSync();
        }, 2000);
        
        // Then sync periodically
        this.syncTimer = setInterval(() => {
            this.performSync();
        }, this.syncInterval);
    }

    /**
     * Update the UI status indicator
     */
    updateStatusIndicator() {
        const statusEl = document.getElementById('apiStatus');
        const textEl = document.getElementById('apiStatusText');
        
        if (!statusEl || !textEl) return;

        if (this.isApiAvailable) {
            statusEl.className = 'api-status-indicator connected';
            
            if (this.lastSyncTime) {
                const elapsed = Math.floor((new Date() - this.lastSyncTime) / 1000);
                textEl.textContent = `API Connected • Synced ${elapsed}s ago`;
            } else {
                textEl.textContent = 'API Connected • Syncing...';
            }
        } else {
            statusEl.className = 'api-status-indicator disconnected';
            textEl.textContent = 'API Offline';
        }
    }

    /**
     * Stop the background sync service
     */
    stop() {
        if (this.syncTimer) {
            clearInterval(this.syncTimer);
            this.syncTimer = null;
        }
        
        if (this.statusTimer) {
            clearInterval(this.statusTimer);
            this.statusTimer = null;
        }
        
        console.log('[BackgroundSync] Service stopped');
    }
}

// Initialize the background sync service when page loads
let backgroundSync = null;

document.addEventListener('DOMContentLoaded', () => {
    backgroundSync = new BackgroundSyncService();
    backgroundSync.init();
    
    console.log('[BackgroundSync] Auto-sync enabled - checking every 10 seconds');
});

// Clean up when page unloads
window.addEventListener('beforeunload', () => {
    if (backgroundSync) {
        backgroundSync.stop();
    }
});

// Update status indicator more frequently
setInterval(() => {
    if (backgroundSync) {
        backgroundSync.updateStatusIndicator();
    }
}, 1000);
