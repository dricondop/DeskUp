function getLayoutData() {
    return Array.from(document.querySelectorAll('.desk')).map(desk => ({
        name: desk.querySelector('.desk-label')?.textContent || 'Desk',
        x: parseInt(desk.style.left),
        y: parseInt(desk.style.top)
    }));
}

document.getElementById('saveLayout')?.addEventListener('click', async () => {
    const desks = getLayoutData();
    
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

document.getElementById('downloadJSON')?.addEventListener('click', () => {
    const desks = getLayoutData();
    const jsonData = JSON.stringify({ desks }, null, 2);
    const blob = new Blob([jsonData], { type: 'application/json' });
    const url = URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.href = url;
    link.download = `layout-${new Date().toISOString().slice(0, 10)}.json`;
    link.click();
    URL.revokeObjectURL(url);
    alert('Layout downloaded successfully!');
});

document.getElementById('uploadJSON')?.addEventListener('click', () => {
    document.getElementById('uploadJSONInput').click();
});

document.getElementById('uploadJSONInput')?.addEventListener('change', (event) => {
    const file = event.target.files[0];
    if (!file) return;
    
    const reader = new FileReader();
    reader.onload = (e) => {
        try {
            const data = JSON.parse(e.target.result);
            if (!data.desks || !Array.isArray(data.desks)) {
                alert('Invalid JSON format. Expected format: {"desks": [{"name": "Desk 1", "x": 100, "y": 100}]}');
                return;
            }
            
            document.querySelectorAll('.desk').forEach(desk => desk.remove());
            
            data.desks.forEach(desk => {
                if (typeof window.addDesk === 'function') {
                    window.addDesk(desk.x, desk.y, desk.name);
                }
            });
            
            alert('Layout uploaded successfully!');
            event.target.value = '';
        } catch (error) {
            console.error('Error parsing JSON:', error);
            alert('Error parsing JSON file. Please check the file format.');
        }
    };
    reader.readAsText(file);
});
