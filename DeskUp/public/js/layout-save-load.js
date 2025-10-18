document.getElementById('saveLayout').addEventListener('click', () => {
    const desks = Array.from(document.querySelectorAll('.desk')).map(desk => ({
        name: desk.querySelector('.desk-label')?.textContent || 'Desk',
        x: parseInt(desk.style.left),
        y: parseInt(desk.style.top)
    }));
    
    const blob = new Blob([JSON.stringify({ desks }, null, 2)], { type: 'application/json' });
    const url = URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.href = url;
    link.download = 'layout.json';
    link.click();
    URL.revokeObjectURL(url);
});

document.getElementById('loadLayout').addEventListener('change', (e) => {
    const file = e.target.files[0];
    if (!file) return;
    
    const reader = new FileReader();
    reader.onload = (event) => {
        try {
            const data = JSON.parse(event.target.result);
            
            if (!data.desks || !Array.isArray(data.desks)) {
                alert('Invalid layout file.');
                return;
            }
            
            document.querySelectorAll('.desk').forEach(desk => desk.remove());
            if (typeof selectedDesks !== 'undefined') selectedDesks.clear();
            
            data.desks.forEach(d => addDesk(d.x, d.y, d.name));
            
            deskCounter = Math.max(...data.desks.map(d => parseInt(d.name.match(/\d+/)?.[0] || 0))) + 1;
            
            alert('Layout loaded successfully!');
        } catch (err) {
            alert('Error reading layout file: ' + err.message);
        }
    };
    reader.readAsText(file);
});
