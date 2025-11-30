// Ideal Height Page JavaScript - Minimal
document.addEventListener('DOMContentLoaded', function() {
    // Add subtle hover effects to instruction items
    const instructionItems = document.querySelectorAll('.instruction-item');
    
    instructionItems.forEach(item => {
        item.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-2px)';
        });
        
        item.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0)';
        });
    });
});