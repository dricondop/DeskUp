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

// Call this function when DOM is loaded
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', setupAdminStatsPDFExport);
} else {
    setupAdminStatsPDFExport();
}