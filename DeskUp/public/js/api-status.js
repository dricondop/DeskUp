/**
 * API Status Checker
 * Checks the DeskSync API status on page load
 */

document.addEventListener('DOMContentLoaded', function() {
    const statusIndicator = document.getElementById('apiStatus');
    const statusText = document.getElementById('apiStatusText');
    
    if (!statusIndicator || !statusText) {
        console.warn('API status elements not found');
        return;
    }

    // Check API status on page load
    checkApiStatus();

    // Add click handler to trigger sync
    statusIndicator.addEventListener('click', handleSync);
    statusIndicator.style.cursor = 'pointer';

    async function handleSync() {
        // If offline, show not available message
        if (statusIndicator.classList.contains('offline')) {
            const originalText = statusText.textContent;
            statusText.textContent = 'Not Available';
            setTimeout(() => {
                statusText.textContent = originalText;
            }, 2000);
            return;
        }

        // Don't trigger if already checking/syncing
        if (statusIndicator.classList.contains('checking')) {
            return;
        }

        try {
            updateStatus('checking', 'Syncing desks...');

            const response = await fetch('/sync-all-desks-data', {
                method: 'GET',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                }
            });

            if (response.ok) {
                const data = await response.json();
                updateStatus('online', `Synced ${data.results?.synced || 0} desks`);
                
                // Revert to normal status after 3 seconds
                setTimeout(() => {
                    updateStatus('online', 'Desk Sync: Online');
                }, 3000);
            } else {
                updateStatus('offline', 'Sync failed');
                setTimeout(() => checkApiStatus(), 2000);
            }
        } catch (error) {
            console.error('Sync failed:', error);
            updateStatus('offline', 'Sync error');
            setTimeout(() => checkApiStatus(), 2000);
        }
    }

    async function checkApiStatus() {
        try {
            // Set to checking state
            updateStatus('checking', 'Checking API...');

            const response = await fetch('/api/check-status', {
                method: 'GET',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                }
            });

            if (response.ok) {
                const data = await response.json();
                
                if (data.status === 'online') {
                    updateStatus('online', 'Desk Sync: Online');
                } else {
                    updateStatus('offline', 'Desk Sync: Offline');
                }
            } else {
                updateStatus('offline', 'Desk Sync: Unavailable');
            }
        } catch (error) {
            console.error('API status check failed:', error);
            updateStatus('offline', 'Desk Sync: Connection Failed');
        }
    }

    function updateStatus(status, message) {
        // Remove all status classes
        statusIndicator.classList.remove('checking', 'online', 'offline');
        
        // Add the new status class
        statusIndicator.classList.add(status);
        
        // Update text
        statusText.textContent = message;
    }
});
