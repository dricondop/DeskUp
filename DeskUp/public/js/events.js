// Load Lucide icons
document.addEventListener('DOMContentLoaded', () => {
    lucide.createIcons();
});

// Apply background color to event-date
const eventDateContainer = document.querySelectorAll('.event-date');


// Open meeting-management
const meetingPanel = document.querySelector('.meeting-management');
const includedDesksElement = document.getElementById('included-desks');
const includedUsersElement = document.getElementById('included-users');
const eventContainers = document.querySelectorAll('.event-container');

let currentEventId = null;      // necessary for remembering which event is open

eventContainers.forEach(container => {
    container.addEventListener('click', () => {
        
        // If the container is already selected, close meeting panel
        if (container.classList.contains('active')) {
            container.classList.remove('active');
            meetingPanel.classList.add('hidden');
            meetingPanel.classList.remove('visible');

        }
        // open meeting panel
        else {
            meetingPanel.classList.remove('hidden');
            
            eventContainers.forEach(c => c.classList.remove('active'));
            container.classList.add('active');

            requestAnimationFrame(() => {
                meetingPanel.classList.add('visible');
            });

            // prepare desks for height control
            const deskIdsJson = container.dataset.includedDeskids;
            const deskIds = deskIdsJson ? JSON.parse(deskIdsJson) : [];
            addAllDesks(deskIds);
            isEvent = true;     // desk-control.js variable

            // Set time window for this event
            const scheduledAtJson = container.dataset.scheduledAt;
            const scheduledToJson = container.dataset.scheduledTo;

            // variables in desk-control.js
            eventStartTime = scheduledAtJson ? new Date(JSON.parse(scheduledAtJson)) : null;
            eventEndTime = scheduledToJson ? new Date(JSON.parse(scheduledToJson)) : null;
            
            // Add desks and users to the meeting panel
            const desksJson = container.dataset.includedDesks;
            const usersJson = container.dataset.includedUsers;

            let desks = [];
            let users = [];

            if (desksJson && usersJson) {
                try {
                    desks = JSON.parse(desksJson);
                    users = JSON.parse(usersJson);
                } catch (error) {
                    console.error('Invalid JSON desks or users', error);
                }
            }

            includedDesksElement.innerHTML = '';
            includedUsersElement.innerHTML = '';
            
            // it is possible to have no users assigned for now
            if (!desks.length) {
                includedDesksElement.textContent = 'No desks assigned';
            } else {
                desks.forEach(deskName => {
                    const item = document.createElement('p');
                    item.textContent = deskName;
                    item.classList.add('desk-tag')
                    includedDesksElement.appendChild(item);
                })
                users.forEach(userName => {
                    const item = document.createElement('p');
                    item.textContent = userName;
                    item.classList.add('user-tag');
                    includedUsersElement.appendChild(item);
                })
            }

            // find available users 
            const eventId = container.dataset.eventId;
            currentEventId = eventId;       // remember which event is open for adding user to event
            loadAvailableUsers(eventId);
        }

    })
})

// close meeting panel
document.querySelector('.close-panel').addEventListener('click', () => {
    meetingPanel.classList.remove('visible');

    // waits with adding 'hidden' until the transition is done
    const handler = (e) => {
        if (e.propertyName === 'transform') {
            meetingPanel.classList.add('hidden');
            meetingPanel.removeEventListener('transitionend', handler);
        }
    };

    meetingPanel.addEventListener('transitionend', handler);

    eventContainers.forEach(c => c.classList.remove('active'));
});

// <select> for adding users to event
const availableUsers = document.getElementById('availableUsers');
availableUsers.addEventListener('change', () => {
    const userId = availableUsers.value;
    if (!userId || !currentEventId) return;

    addUserToEvent(userId, currentEventId);
    availableUsers.value = "";
})


// find available users for event
async function loadAvailableUsers(eventId) 
{
    try {
        const response = await fetch(`/event/${eventId}/availableUsers`);
        const users = await response.json();

        availableUsers.innerHTML = '<option value="">Select user</option>';

        users.forEach(user => {
            const option = document.createElement('option');
            option.value = user.id;
            option.textContent = user.name;
            availableUsers.appendChild(option);
        });

    } catch (error) {
        console.error(`Failed to find available users`, error);
    }
}

async function addUserToEvent(userId, eventId) 
{
    const payload = {user: userId};
    
    try {
        const response = await fetch(`/event/${eventId}/addUser`, {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "X-CSRF-TOKEN": csrfToken,
            },
            body: JSON.stringify(payload)
        });

        const data = await response.json();
        if (data.success) {
            // add a new user tag to 'Attendees'
            const item = document.createElement('p');
                    item.textContent = data.user_name;
                    item.classList.add('user-tag');
                    includedUsersElement.appendChild(item);
            
            // remove user from <select>
            const optionToRemove = availableUsers.querySelector(`option[value="${userId}"]`);
            if (optionToRemove) {
                optionToRemove.remove();
            }
            
        }

    } catch (error) {
        console.error(`Failed to add userId ${userId} to eventId ${eventId}`, error);
    }
}