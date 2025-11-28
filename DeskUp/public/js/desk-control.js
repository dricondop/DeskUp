
const display = document.getElementById("height-display");
const inc = document.getElementById("increment");
const dec = document.getElementById("decrement");

display.textContent = desk.height + " cm" ?? 0;

// Button-Hold function
function holdButton(btn, placeholderFunction, holdDelay = 1000) {
    let activationTimeout;
    let repeatTimeout;
    let isHolding = false;
    
    const start = () => {
        if (isHolding) return;
        isHolding = true;
        placeholderFunction(); // Immediately changes +/- 1 value, functions as ordinary click
        activationTimeout = setTimeout(run, holdDelay);
    };

    const run = () => {
        if (!isHolding) return;
        placeholderFunction();
        // const repeatDelay = desk.speed || 1;
        repeatTimeout = setTimeout(run, 50);
    };

    const stop = () => {
        if (!isHolding) return;
        isHolding = false;
        clearTimeout(activationTimeout);
        clearTimeout(repeatTimeout);
        updateDesk("height"); // Sends final height to database
    };

    btn.addEventListener("mousedown", start);
    btn.addEventListener("mouseup", stop);
    btn.addEventListener("mouseleave", stop);
};

holdButton(inc, () => changeHeight(1));
holdButton(dec, () => changeHeight(-1));


function changeHeight(number) {
    desk.height = Math.min(150, Math.max(0, desk.height + number));
    display.textContent = desk.height + " cm";
}

// Save desk height in database
async function updateDesk(field, value) 
{
    const payload = {'height': desk.height};

    try {
        const response = await fetch(`${desk.url}/${desk.id}/${field}`, {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "X-CSRF-TOKEN": csrfToken,
            },
            body: JSON.stringify(payload)
        });

        const data = await response.json(); 
        if (data.success) {
                console.log(`${field} updated successfully`);
            }
        } 

        
    catch (error) {
        console.error(`Error updating ${field}:`, error);
    }
}  

// Switch between Activity or Pending (activities - user view only)
const tabs = document.querySelectorAll('.activity-tab');
const panels = document.querySelectorAll('.activity-panel');

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

// Open modal
function openModal() {
    document.getElementById('activityModal').style.display = 'block';
}

// close modal
function closeModal() {
    document.getElementById('activityModal').style.display = 'none';
}

// Mini layout inside the "Add Activities" for choosing desks
const miniLayout = document.getElementById('miniLayout');
const selectedDesksInputs = document.getElementById('selectedDesksInputs');
const selectedDeskIds = new Set();

if (miniLayout) {
    loadMiniLayout();
}

async function loadMiniLayout() {
    try {
        const resp = await fetch('/layout/load');
        const data = await resp.json();

        if (!data.desks) return;

        // add each desk
        data.desks.forEach(desk => {
            const element = document.createElement('div');
            element.classList.add('mini-desk');
            element.style.left = desk.x / 2 + 'px';
            element.style.top = desk.y / 2 + 'px';
            element.dataset.id = desk.id;

            element.innerHTML = `
                <img src="/desk_icon.png">
                <span>${desk.name}</span>
            `;

            // click to toggle selection
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


// Create activity
const activityForm = document.getElementById('activityForm');

activityForm.addEventListener('submit', async (event) => {
    event.preventDefault(); // this prevents normal form submit

    const date = document.getElementById('meeting-date').value;
    const timeFrom = document.getElementById('meeting-time-from').value;
    const timeTo = document.getElementById('meeting-time-to').value;
    const description = document.getElementById('activityFormDescription').value;

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
        event_type: 'meeting',
        description: description,
        scheduled_at: scheduledAt,
        scheduled_to: scheduledTo,
        desk_ids: Array.from(selectedDeskIds) // convert Set to an array
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
    

    activityForm.reset();
    selectedDeskIds.clear();
    document.querySelectorAll('.mini-desk.selected').forEach(desk => desk.classList.remove('selected'));
    closeModal();
});






