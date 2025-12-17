const eventModal = document.getElementById('eventModal');
const cleaningModal = document.getElementById('cleaningModal');
const modalContent = document.querySelector('.modal-content');

// Open 'Add event' modal
const addEventBtn = document.querySelector('.add-event');
if (addEventBtn && eventModal) {
    addEventBtn.addEventListener('click', () => {
        eventModal.style.display = 'block';

        // load miniLayout
        if (miniLayout && !miniLayout.dataset.initialized) {
            loadMiniLayout();
            miniLayout.dataset.initialized = '1';
        }
    })
}

// Open 'Cleaning Schedule' modal
const scheduleCleaningBtn = document.querySelector('.scheduleCleaning');
if (scheduleCleaningBtn && cleaningModal) {
    scheduleCleaningBtn.addEventListener('click', () => {
        cleaningModal.style.display = 'block';
    })
}


// close modal
function closeModal(modal) {
    modal.style.display = 'none';

    if (modal === eventModal) {
        // clear selected desks  
        selectedDeskIds.clear();

        // remove 'selected' style on previously chosen desks
        document.querySelectorAll('.mini-desk.selected').forEach(desk => desk.classList.remove('selected'));
    }
}
// close modal through button
document.querySelectorAll('.closeModal').forEach(button => {
    button.addEventListener('click', () => {
        const modalElement = button.closest('.modal');
        if (modalElement) {
            closeModal(modalElement);
        }
    })
})

// Close 'Add event' modal if clicking outside the modal when open
window.addEventListener('click', (event) => {
    if (eventModal && eventModal.style.display === 'block' && event.target === eventModal) {
        closeModal(eventModal);
    }
    if (cleaningModal && cleaningModal.style.display === 'block' && event.target === cleaningModal) {
        closeModal(cleaningModal);
    }
})

// add users to event
const userSelect = document.getElementById('userSelect');
const selectedUserIds = new Set();
selectedUserIds.add(loggedInUser); // adds logged-in user by default

if (userSelect) userSelect.addEventListener('change', () => {
    const value = userSelect.value;
    if (!value) return;

    const userId = Number(value);
    const option = userSelect.selectedOptions[0];

    if (selectedUserIds.has(userId) && userId !== loggedInUser) {
        selectedUserIds.delete(userId);
        option.classList.remove('userSelected');
        userSelect.value = "";
        return;
    }
    else if (userId === loggedInUser) {
        return;     // the logged-in user will always be added to events
    }

    selectedUserIds.add(userId);
    option.classList.add('userSelected');
});


// Mini layout inside the "Add Activities" for choosing desks
const miniLayout = document.getElementById('miniLayout');
const selectedDeskIds = new Set();

async function loadMiniLayout() {
    try {
        const resp = await fetch('/layout/load');
        const data = await resp.json();

        if (!data.desks || data.desks.length === 0) return;

        // find desk positions to scale layout properly
        const xs = data.desks.map(d => d.x);
        const ys = data.desks.map(d => d.y);

        const minX = Math.min(...xs);
        const maxX = Math.max(...xs);
        const minY = Math.min(...ys);
        const maxY = Math.max(...ys);

        const layoutWidth  = maxX - minX || 1;
        const layoutHeight = maxY - minY || 1;

        // gets the miniLayout's size
        const miniWidth  = miniLayout.clientWidth;
        const miniHeight = miniLayout.clientHeight;

        // space and padding for desk icons
        const iconW   = 60;  // matches .mini-desk width
        const iconH   = 50;  // approx total height height
        const padding = 10;

        // calculates uniform scale so everything fits
        const scaleX = (miniWidth  - iconW  - padding * 2) / layoutWidth;
        const scaleY = (miniHeight - iconH - padding * 2) / layoutHeight;
        const scale  = Math.min(scaleX, scaleY, 1); // never upscale above 1

        // clear old layout
        miniLayout.innerHTML = '';

        // add and place each desk on layout
        data.desks.forEach(desk => {
            const element = document.createElement('div');
            element.classList.add('mini-desk');
            element.dataset.id = desk.id;

            // calculates the correct positions for each desk to fit within the shrinked miniLayout
            const x = (desk.x - minX) * scale + padding;
            const y = (desk.y - minY) * scale + padding;

            element.style.left = `${x}px`;
            element.style.top  = `${y}px`;

            element.innerHTML = `
                <img src="/desk_icon.png">
                <span>${desk.name}</span>
            `;
            
            // make desks clickable and add to Set
            element.addEventListener('click', () => {
                if (selectedDeskIds.has(desk.id)) {
                    selectedDeskIds.delete(desk.id);
                    element.classList.remove('selected');
                } else {
                    selectedDeskIds.add(desk.id);
                    element.classList.add('selected');
                }
            });

            miniLayout.appendChild(element);
        });
    } catch (err) {
        console.error('Failed to load mini-layout:', err);
    }
}

// Create event
const eventForm = document.getElementById('eventForm');

if (eventForm) eventForm.addEventListener('submit', async (event) => {
    event.preventDefault(); // this prevents normal form submit

    const eventType = document.getElementById('eventTypeSelect').value;
    const date = document.getElementById('meeting-date').value;
    const timeFrom = document.getElementById('meeting-time-from').value;
    const timeTo = document.getElementById('meeting-time-to').value;
    const description = document.getElementById('eventFormDescription').value;

    const scheduledAt = `${date} ${timeFrom}:00`;
    let scheduledToDate = date; 
    
    // if end time is earlier/equal, it's a new day
    if (timeFrom >= timeTo) {
        const d = new Date(date);   // gets the meeting start date
        d.setDate(d.getDate() + 1); // adds one day
        
        const pad = n => String(n).padStart(2, '0'); // Adds zeroes, e.g. 2025-1-7 becomes 2025-01-07
        scheduledToDate = `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}`;
    }
    const scheduledTo = `${scheduledToDate} ${timeTo}:00`;

    const payload = {
        event_type: eventType,
        description: description,
        scheduled_at: scheduledAt,
        scheduled_to: scheduledTo,
        desk_ids: Array.from(selectedDeskIds), // convert Set to an array
        user_ids: Array.from(selectedUserIds), // convert Set to an array
    };
   
    try {
        const response = await fetch(`/api/addEvent`, {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "X-CSRF-TOKEN": csrfToken,
            },
            body: JSON.stringify(payload)
        });

        const data = await response.json(); 

        if (!response.ok) {
            console.error('Server error', response.status, data);
            return;
        }

        if (!data.success) {
                console.error('Application/validation error', data);
                return;
            }
        
        console.log('Event created', data.event);
    
    } catch (error) {
        console.error(`Failed to create an event`, error);
    }
    

    eventForm.reset();
    selectedDeskIds.clear();
    document.querySelectorAll('.mini-desk.selected').forEach(desk => desk.classList.remove('selected'));
    closeModal(eventModal);
});


// choose days for cleaning
document.querySelectorAll('.scheduleDay').forEach(button => {
    button.addEventListener('click', () => {
        button.classList.toggle('active');
    })
})


// cleaning modal submission
const cleaningForm = document.getElementById('cleaningForm');
if (cleaningForm) cleaningForm.addEventListener('submit', async (event) => {
    event.preventDefault();

    const time = document.getElementById('cleaningTime').value;
    const activeDays = Array.from(document.querySelectorAll('.scheduleDay.active')).map(btn => btn.dataset.day);

    if (activeDays.length === 0) {
        console.log('Please select at least one day');
        return;
    }

    const payload = {
        cleaning_time: time,
        cleaning_days: activeDays
    }

    try {
        const response = await fetch('/api/addCleaningSchedule', {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "X-CSRF-TOKEN": csrfToken,
            },
            body: JSON.stringify(payload)
        });

        const data = await response.json();

        if (!response.ok) {
            const text = await response.text();
            console.error(text);
            return;
        }

        if (data.success) {
            
        }


    } catch (error) {
        console.error(`Failed to create a cleaning schedule`, error);
    }

    closeModal(cleaningModal);
})