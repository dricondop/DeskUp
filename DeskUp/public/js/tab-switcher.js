// Switch between tabs
const tabs = document.querySelectorAll('.event-tab');
const panels = document.querySelectorAll('.event-panel');

tabs.forEach(tab => {
    tab.addEventListener('click', () => {

        // switch active tab
        tabs.forEach(t => t.classList.remove('active'));
        tab.classList.add('active');

        // show correct panel
        const targetPanel = tab.dataset.target;
        panels.forEach(p => {
            if (p.id === targetPanel) {
                p.classList.remove('hidden');
            } 
            else {
                p.classList.add('hidden');
            }
        })
    })
})