// Get CSRF token
const csrfToken = document.querySelector('meta[name="csrf-token"]').content;

// ============================================
// USER MANAGEMENT FUNCTIONALITY
// ============================================

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
function showDescriptionModal(description) {
    document.getElementById('descriptionText').textContent = description;
    document.getElementById('descriptionModal').style.display = 'block';
    setTimeout(() => lucide.createIcons(), 0);
}

// Desks modal
function showDesksModal(desks) {
    document.getElementById('desksText').textContent = desks;
    document.getElementById('desksModal').style.display = 'block';
    setTimeout(() => lucide.createIcons(), 0);
}

// Close modal
document.querySelectorAll('.closeModal').forEach(button => {
    button.addEventListener('click', () => {
        document.getElementById('descriptionModal').style.display = 'none';
        document.getElementById('desksModal').style.display = 'none';
        document.getElementById('createUserModal').style.display = 'none';
    })
});

// Close description modal if clicking outside the modal when open
window.onclick = function(event) {
    const descriptionModal = document.getElementById('descriptionModal');
    const desksModal = document.getElementById('desksModal');
    const createUserModal = document.getElementById('createUserModal');

    if (event.target === descriptionModal) {
        document.getElementById('descriptionModal').style.display = 'none';
    }

    if (event.target === desksModal) {
        document.getElementById('desksModal').style.display = 'none';
    }

    if (event.target === createUserModal) {
        document.getElementById('createUserModal').style.display = 'none';
    }
}

// Create User Modal
const createUserBtn = document.getElementById('createUserBtn');
const createUserModal = document.getElementById('createUserModal');
const submitCreateUserBtn = document.getElementById('submitCreateUser');
const createUserForm = document.getElementById('createUserForm');

if (createUserBtn) {
    createUserBtn.addEventListener('click', () => {
        createUserModal.style.display = 'block';
        createUserForm.reset();
    });
}

if (submitCreateUserBtn) {
    submitCreateUserBtn.addEventListener('click', async (e) => {
        e.preventDefault();
        
        const formData = new FormData(createUserForm);
        const data = {
            name: formData.get('name'),
            email: formData.get('email'),
            password: formData.get('password'),
            is_admin: document.getElementById('isAdmin').checked
        };

        try {
            const response = await fetch('/user/create', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                },
                body: JSON.stringify(data)
            });

            const result = await response.json();

            if (result.success) {
                alert('User created successfully!');
                createUserModal.style.display = 'none';
                location.reload();
            } else {
                let errorMsg = result.message || 'Failed to create user';
                if (result.errors) {
                    errorMsg += '\n' + Object.values(result.errors).flat().join('\n');
                }
                alert(errorMsg);
            }
        } catch (error) {
            console.error('Error:', error);
            alert('Network error: ' + error.message);
        }
    });
}

