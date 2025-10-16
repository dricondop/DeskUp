// Save Layout as JSON file
function saveLayoutAsFile() {
    const desks = Array.from(document.querySelectorAll('.desk')).map(desk => {
        const label = desk.querySelector('.desk-label');
        return {
            name: label ? label.textContent : 'Desk',
            x: parseInt(desk.style.left),
            y: parseInt(desk.style.top)
        };
    });
    const dataStr = JSON.stringify({ desks }, null, 2);
    const blob = new Blob([dataStr], { type: 'application/json' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = 'layout.json';
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    URL.revokeObjectURL(url);
}
document.getElementById('saveLayout').addEventListener('click', saveLayoutAsFile);

// Load Layout from uploaded JSON file
function handleLoadLayout(event) {
    const file = event.target.files[0];
    if (!file) return;
    const reader = new FileReader();
    reader.onload = function(e) {
        try {
            const json = JSON.parse(e.target.result);
            if (!json.desks || !Array.isArray(json.desks)) {
                alert('Invalid layout file.');
                return;
            }
            // Clear current desks
            document.querySelectorAll('.desk').forEach(desk => desk.remove());
            if (typeof selectedDesks !== 'undefined') {
                selectedDesks.clear();
            }
            // Load desks from file
            json.desks.forEach(deskData => {
                if (typeof addDesk === 'function') {
                    addDesk(deskData.x, deskData.y, deskData.name);
                }
            });
            // Update counter
            const maxNum = Math.max(...json.desks.map(d => {
                const match = d.name.match(/\d+/);
                return match ? parseInt(match[0]) : 0;
            }));
            if (typeof deskCounter !== 'undefined') {
                deskCounter = maxNum + 1;
            }
            alert('Layout loaded from file!');
        } catch (err) {
            alert('Error reading layout file.');
        }
    };
    reader.readAsText(file);
}
document.getElementById('loadLayout').addEventListener('change', handleLoadLayout);
