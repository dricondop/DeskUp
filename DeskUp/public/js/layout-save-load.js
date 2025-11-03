document.getElementById('saveLayout').addEventListener('click', async () => {
    const desks = Array.from(document.querySelectorAll('.desk')).map(desk => ({
        name: desk.querySelector('.desk-label')?.textContent || 'Desk',
        x: parseInt(desk.style.left),
        y: parseInt(desk.style.top)
    }));
    
    try {
        const response = await fetch('/layout/save', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({ desks })
        });

        const data = await response.json();
        
        if (data.success) {
            alert('Layout saved successfully to database!');
            
            location.reload();
        } else {
            alert('Error saving layout: ' + (data.message || 'Unknown error'));
        }
    } catch (error) {
        console.error('Error saving layout:', error);
        alert('Error saving layout. Check console for details.');
    }
});
