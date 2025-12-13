// Get CSRF token
const csrfToken = document.querySelector('meta[name="csrf-token"]').content;

// Assign desk to user
document.addEventListener('change', (e) => {
  if (e.target.matches('.desk-select')) {
    assignDesk(e.target);
  }
});

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

// Approve events
document.querySelectorAll('.btn-approve').forEach(button => {
    button.addEventListener('click', async () => {
        const eventId = Number(button.dataset.eventId);

        try {
            const response = await fetch(`/event/${eventId}/approve`, {
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
            console.error(`Failed to approve event with id ${eventId}`, error);
        }
    })
})

// Reject event
document.querySelectorAll('.btn-reject').forEach(button => {
    button.addEventListener('click', async () => {
        const eventId = Number(button.dataset.eventId);

        try {
            const response = await fetch(`/event/${eventId}/reject`, {
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
            console.error(`Failed to reject event with id ${eventId}`, error);
        }
    })
})



// Description modal
function showMessage(description) {
    document.getElementById('descriptionText').textContent = description;
    document.getElementById('descriptionModal').style.display = 'block';
    setTimeout(() => lucide.createIcons(), 0);
}

// Desks modal
function showDesks(desks) {
    document.getElementById('desksText').textContent = desks;
    document.getElementById('desksModal').style.display = 'block';
    setTimeout(() => lucide.createIcons(), 0);
}

// Close modal
document.querySelectorAll('.closeModal').forEach(button => {
    button.addEventListener('click', () => {
        document.getElementById('descriptionModal').style.display = 'none';
        document.getElementById('desksModal').style.display = 'none';
    })
});

// Close description modal if clicking outside the modal when open
window.onclick = function(event) {
    const descriptionModal = document.getElementById('descriptionModal');
    const desksModal = document.getElementById('desksModal');

    if (event.target === descriptionModal) {
        document.getElementById('descriptionModal').style.display = 'none';
    }

    if (event.target === desksModal) {
        document.getElementById('desksModal').style.display = 'none';
    }

}

