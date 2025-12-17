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

if (saveSettingsBtn) {
    saveSettingsBtn.addEventListener('click', async () => {
        try {
            const response = await fetch('/api/notifications/settings', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
                body: JSON.stringify({
                    automatic_notifications_enabled: autoNotificationsToggle.checked,
                    sitting_time_threshold_minutes: parseInt(sittingThreshold.value),
                }),
            });

            const data = await response.json();

            if (data.success) {
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
const notificationMessageEl = document.getElementById('notificationMessage');
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
                showMessage(notificationMessageEl, 'Please select at least one user or check "Send to all users"', 'error');
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
                showMessage(notificationMessageEl, `Notification sent to ${result.count} user(s)!`, 'success');
                manualNotificationForm.reset();
                charCount.textContent = '0';
                userSelectGroup.style.display = 'block';
            } else {
                showMessage(notificationMessageEl, 'Failed to send notification', 'error');
            }
        } catch (error) {
            console.error('Error sending notification:', error);
            showMessage(notificationMessageEl, 'An error occurred', 'error');
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

