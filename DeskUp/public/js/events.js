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
        }

    })
})

// close meeting panel
document.querySelector('.close-panel').addEventListener('click', () => {
    meetingPanel.classList.add('hidden');
    meetingPanel.classList.remove('visible');
    eventContainers.forEach(c => c.classList.remove('active'));
});