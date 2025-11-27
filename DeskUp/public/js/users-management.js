// Get CSRF token
const csrfToken = document.querySelector('meta[name="csrf-token"]').content;

// Populate the select with unassigned desks
document.querySelectorAll('.desk-select').forEach(select => {
    Object.entries(window.desks).forEach(([id, name]) => {
        const option = document.createElement('option');
        option.value = id;
        option.textContent = name;
        select.appendChild(option);
    });

    select.addEventListener('change', () => {
        assignDesk(select);
    });
});

// Assign desk to user
async function assignDesk(select) 
{
    const userId = select.dataset.userId
    const payload = { 
        assigned_desk_id: Number(select.value)
    }

    try {
        const response = await fetch(`/user/${userId}/assign-desk-id`, {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "X-CSRF-TOKEN": csrfToken,
            },
            body: JSON.stringify(payload)
        });
        
        const data = await response.json();
        if (data.success) {
            window.location.reload();
        }
        
    } catch (error) {
        console.error(`Failed to assign deskId ${payload.value} to user ${userId}`, error);
    }
}

// Unnasign desk from user
document.querySelectorAll('.btn-unassign').forEach(button => {
    button.addEventListener('click', async () => {
    const userId = Number(button.dataset.userId);

        try {
            const response = await fetch(`/user/${userId}/unassign-desk-id`, {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN": csrfToken,
                }
            });

            const data = await response.json();
            if (data.success) {
                window.location.reload();
            }

        } catch (error) {
            console.error(`Failed to unassign desk for user ${userId}`);
        }
    })
})

// Remove user
document.querySelectorAll('.btn-remove').forEach(button => {
    button.addEventListener('click', async () => {
        const userId = Number(button.dataset.userId);

        try {
            const response = await fetch(`/user/${userId}/remove-user`, {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN": csrfToken,
                }
            });

            const data = await response.json();
            if (data.success) {
                window.location.reload();
            }
        } catch (error) {
            console.error(`Failed to remove user ${userId}`, error);
        }
    })
})







function toggleDesk(element) {
    const isActive = element.classList.contains('active');
    if (isActive) {
        element.textContent = 'Disabled';
        element.classList.remove('active');
        element.classList.add('inactive');
    } else {
        element.textContent = 'Active';
        element.classList.remove('inactive');
        element.classList.add('active');
    }
}

function removeUser(button) {
    const row = button.closest('tr');
    row.classList.add('fade-out');
    setTimeout(() => row.remove(), 400);
}

function updateDesk(select) {
    const user = select.closest('tr').querySelector('td:first-child').textContent;
    const desk = select.value;
    console.log(`${user} assigned to ${desk}`);
}
