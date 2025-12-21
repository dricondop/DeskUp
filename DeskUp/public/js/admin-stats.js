// PDF Export functionality for admin statistics
function setupAdminStatsPDFExport() {
    const exportBtn = document.getElementById('export-stats-pdf-btn');
    if (!exportBtn) return;
    
    exportBtn.addEventListener('click', async () => {
        const originalText = exportBtn.innerHTML;
        exportBtn.innerHTML = '<span>Exporting...</span>';
        exportBtn.disabled = true;

        try {
            const url = '/admin/statistics/export/pdf';
            window.open(url, '_blank');
        } catch (error) {
            console.error('Export error:', error);
            alert('Failed to generate PDF. Please try again.');
        } finally {
            // Restaurar botón
            exportBtn.innerHTML = originalText;
            exportBtn.disabled = false;
        }
    });
}

// Store previous values to detect changes
let previousData = null;

// Fetch and update live statistics data
async function updateLiveStatistics() {
    try {
        const response = await fetch('/api/admin-statistics/live');
        
        if (!response.ok) {
            throw new Error('Failed to fetch live statistics');
        }
        
        const data = await response.json();
        
        // Only update if data has changed
        if (!previousData || 
            previousData.occupiedDesks !== data.occupiedDesks || 
            previousData.availableDesks !== data.availableDesks) {
            
            // Update metrics on the page
            const occupiedEl = document.getElementById('desks-occupied');
            const freeEl = document.getElementById('desks-free');
            
            if (occupiedEl && occupiedEl.textContent != data.occupiedDesks) {
                occupiedEl.textContent = data.occupiedDesks;
            }
            if (freeEl && freeEl.textContent != data.availableDesks) {
                freeEl.textContent = data.availableDesks;
            }
            
            // Update the donut chart if it exists
            if (window.desksDonut) {
                window.desksDonut.data.datasets[0].data = [data.occupiedDesks, data.availableDesks];
                window.desksDonut.update('none'); // Use 'none' mode for instant update without animation
            }
        }
        
        // Update average session if changed
        const avgSessionEl = document.getElementById('avg-session');
        if (avgSessionEl && (!previousData || previousData.avgSession !== data.avgSession)) {
            avgSessionEl.textContent = data.avgSession + ' min';
        }
        
        // Update top users chart only if data changed
        if (window.topUsersChart && data.topUsers && 
            (!previousData || JSON.stringify(previousData.topUsers) !== JSON.stringify(data.topUsers))) {
            const newLabels = data.topUsers.map(u => u.name);
            const newValues = data.topUsers.map(u => u.count);
            
            window.topUsersChart.data.labels = newLabels;
            window.topUsersChart.data.datasets[0].data = newValues;
            window.topUsersChart.update('none'); // Use 'none' mode for instant update
        }
        
        // Update desk list only if changed
        if (data.deskList && (!previousData || JSON.stringify(previousData.deskList) !== JSON.stringify(data.deskList))) {
            const deskListEl = document.getElementById('deskList');
            if (deskListEl) {
                let html = '';
                data.deskList.forEach(desk => {
                    html += `
                        <div class="desk-row">
                            <div style="display:flex; gap:10px; align-items:center;">
                                <strong style="min-width:90px; color:#3A506B;">${desk.name}</strong>
                                <small class="muted small">Status: ${desk.status}</small>
                            </div>
                            <div style="display:flex; gap:10px; align-items:center;">
                                <span class="avg-badge">${desk.avgTime} min</span>
                            </div>
                        </div>
                    `;
                });
                deskListEl.innerHTML = html;
            }
        }
        
        // Store current data for next comparison
        previousData = data;
        
    } catch (error) {
        console.error('Error updating live statistics:', error);
    }
}

// Call this function when DOM is loaded
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        setupAdminStatsPDFExport();
        // Update statistics every 10 seconds for better performance
        updateLiveStatistics();
        setInterval(updateLiveStatistics, 10000);
    });
} else {
    setupAdminStatsPDFExport();
    updateLiveStatistics();
    setInterval(updateLiveStatistics, 10000);
}
