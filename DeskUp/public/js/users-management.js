// Get CSRF token
const csrfToken = document.querySelector('meta[name="csrf-token"]').content;

// ============================================
// NOTIFICATION MANAGEMENT FUNCTIONALITY
// ============================================

// Settings Management
const saveSettingsBtn = document.getElementById('saveSettingsBtn');
const autoNotificationsToggle = document.getElementById('autoNotificationsToggle');
const sittingThreshold = document.getElementById('sittingThreshold');
const settingsMessage = document.getElementById('settingsMessage');

// Load settings from localStorage on page load
if (autoNotificationsToggle && sittingThreshold) {
    const savedSettings = localStorage.getItem('notificationSettings');
    if (savedSettings) {
        const settings = JSON.parse(savedSettings);
        autoNotificationsToggle.checked = settings.automatic_notifications_enabled;
        sittingThreshold.value = settings.sitting_time_threshold_minutes;
    }
}

if (saveSettingsBtn) {
    saveSettingsBtn.addEventListener('click', async () => {
        const settingsData = {
            automatic_notifications_enabled: autoNotificationsToggle.checked,
            sitting_time_threshold_minutes: parseInt(sittingThreshold.value),
        };

        try {
            const response = await fetch('/api/notifications/settings', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
                body: JSON.stringify(settingsData),
            });

            const data = await response.json();

            if (data.success) {
                // Save to localStorage
                localStorage.setItem('notificationSettings', JSON.stringify(settingsData));
                showMessage(settingsMessage, 'Settings saved successfully!', 'success');
            } else {
                showMessage(settingsMessage, 'Failed to save settings', 'error');
            }
        } catch (error) {
            console.error('Error saving settings:', error);
            showMessage(settingsMessage, 'An error occurred', 'error');
        }
    });
}

// Manual Notification Form
const manualNotificationForm = document.getElementById('manualNotificationForm');
const sendToAll = document.getElementById('sendToAll');
const userSelectGroup = document.getElementById('userSelectGroup');
const notificationFormMessageEl = document.getElementById('notificationFormMessage');
const charCount = document.getElementById('charCount');
const notificationMessageTextarea = document.querySelector('#notificationMessage');

// Character counter
if (notificationMessageTextarea) {
    notificationMessageTextarea.addEventListener('input', (e) => {
        charCount.textContent = e.target.value.length;
    });
}

// Toggle user selection
if (sendToAll) {
    sendToAll.addEventListener('change', () => {
        userSelectGroup.style.display = sendToAll.checked ? 'none' : 'block';
        
        // Uncheck all user checkboxes when "send to all" is checked
        if (sendToAll.checked) {
            document.querySelectorAll('input[name="user_ids[]"]').forEach(cb => {
                cb.checked = false;
            });
        }
    });
}

// Submit manual notification
if (manualNotificationForm) {
    manualNotificationForm.addEventListener('submit', async (e) => {
        e.preventDefault();

        const formData = new FormData(manualNotificationForm);
        const data = {
            title: formData.get('title'),
            message: formData.get('message'),
            send_to_all: sendToAll.checked,
        };

        if (!sendToAll.checked) {
            const selectedUsers = Array.from(document.querySelectorAll('input[name="user_ids[]"]:checked'))
                .map(cb => parseInt(cb.value));
            
            if (selectedUsers.length === 0) {
                showMessage(notificationFormMessageEl, 'Please select at least one user or check "Send to all users"', 'error');
                return;
            }
            
            data.user_ids = selectedUsers;
        }

        try {
            const response = await fetch('/api/notifications/send-manual', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
                body: JSON.stringify(data),
            });

            const result = await response.json();

            if (result.success) {
                showMessage(notificationFormMessageEl, `Notification sent to ${result.count} user(s)!`, 'success');
                manualNotificationForm.reset();
                charCount.textContent = '0';
                userSelectGroup.style.display = 'block';
            } else {
                showMessage(notificationFormMessageEl, 'Failed to send notification', 'error');
            }
        } catch (error) {
            console.error('Error sending notification:', error);
            showMessage(notificationFormMessageEl, 'An error occurred', 'error');
        }
    });
}

function showMessage(element, text, type) {
    if (!element) return;
    element.textContent = text;
    element.className = `message ${type}`;
    element.style.display = 'block';
    
    setTimeout(() => {
        element.style.display = 'none';
    }, 5000);
}

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

