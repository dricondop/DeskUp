const eventModal = document.getElementById('eventModal');
const cleaningModal = document.getElementById('cleaningModal');
const modalContent = document.querySelector('.modal-content');

// Open 'Add event' modal
document.querySelector('.add-event').addEventListener('click', () => {
    eventModal.style.display = 'block';

    // load miniLayout
    if (!miniLayout.dataset.initialized) {
        loadMiniLayout();
        miniLayout.dataset.initialized = '1';
    }
})

// Open 'Cleaning Schedule' modal
document.querySelector('.scheduleCleaning').addEventListener('click', () => {
    cleaningModal.style.display = 'block';
})


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
    if (eventModal.style.display === 'block' && event.target === eventModal) {
        closeModal(eventModal);
    }
    if (cleaningModal.style.display === 'block' && event.target === cleaningModal) {
        closeModal(cleaningModal);
    }
})

// add users to event
const userSelect = document.getElementById('userSelect');
const selectedUserIds = new Set();
selectedUserIds.add(loggedInUser); // adds logged-in user by default

userSelect.addEventListener('change', () => {
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

eventForm.addEventListener('submit', async (event) => {
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
        const response = await fetch(`/api/user/addEvent`, {
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


// choose time for cleaning scheduling
const hhBtn = document.getElementById('hhBtn');
const mmBtn = document.getElementById('mmBtn');
const picker = document.getElementById('timePicker');
const cleaningTime = document.getElementById('cleaningTime');

let openMinOrHour = null; // 'hh' or 'mm'

function padNumber(n) { 
    return String(n).padStart(2, '0'); 
}

function setTime(minOrHour, value) {
  if (minOrHour === 'hh') hhBtn.textContent = padNumber(value);
  else mmBtn.textContent = padNumber(value);

  if (cleaningTime) cleaningTime.value = `${hhBtn.textContent}:${mmBtn.textContent}`;
}

function buildList(minOrHour) {
  const max = minOrHour === 'hh' ? 23 : 59;
  picker.innerHTML = '';
  for (let i = 0; i <= max; i++) {
    const item = document.createElement('button');
    item.type = 'button';
    item.className = 'timeOption';
    item.textContent = padNumber(i);
    item.addEventListener('click', () => {
      setTime(minOrHour, i);
      closePicker();
    });
    picker.appendChild(item);
  }
}

function openPicker(minOrHour, anchorEl) {
  openMinOrHour = minOrHour;
  buildList(minOrHour);
  picker.hidden = false;

  const r = anchorEl.getBoundingClientRect();
  const pr = anchorEl.offsetParent.getBoundingClientRect();
  picker.style.left = (r.left - pr.left) + 'px';
  picker.style.top  = (r.bottom - pr.top + 6) + 'px';

  hhBtn.classList.toggle('active', minOrHour === 'hh');
  mmBtn.classList.toggle('active', minOrHour === 'mm');
}

function closePicker() {
  openMinOrHour = null;
  picker.hidden = true;
  hhBtn.classList.remove('active');
  mmBtn.classList.remove('active');
}

hhBtn.addEventListener('click', () => {
  if (openMinOrHour === 'hh') closePicker();
  else openPicker('hh', hhBtn);
});

mmBtn.addEventListener('click', () => {
  if (openMinOrHour === 'mm') closePicker();
  else openPicker('mm', mmBtn);
});

document.addEventListener('click', (e) => {
  if (!picker.hidden && !picker.contains(e.target) && e.target !== hhBtn && e.target !== mmBtn) {
    closePicker();
  }
});



// choose days for cleaning
document.querySelectorAll('.scheduleDay').forEach(button => {
    button.addEventListener('click', () => {
        button.classList.toggle('active');
    })
})