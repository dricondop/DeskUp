// Admin Notifications JavaScript
const csrfToken = document.querySelector('meta[name="csrf-token"]').content;

// Character counters
const titleInput = document.getElementById('notif-title');
const messageInput = document.getElementById('notif-message');
const titleCount = document.getElementById('title-count');
const messageCount = document.getElementById('message-count');

titleInput?.addEventListener('input', () => {
    titleCount.textContent = titleInput.value.length;
});

messageInput?.addEventListener('input', () => {
    messageCount.textContent = messageInput.value.length;
});

// Toggle switch status update
const autoToggle = document.getElementById('auto-enabled');
const toggleStatus = document.querySelector('.toggle-status');

autoToggle?.addEventListener('change', () => {
    toggleStatus.textContent = autoToggle.checked ? 'Enabled' : 'Disabled';
});

// User selection toggle
function toggleUserSelection() {
    const recipientType = document.querySelector('input[name="recipient-type"]:checked').value;
    const userSelection = document.getElementById('user-selection');
    const sendBtnText = document.getElementById('send-btn-text');
    
    if (recipientType === 'specific') {
        userSelection.style.display = 'flex';
        updateSendButtonText();
    } else {
        userSelection.style.display = 'none';
        sendBtnText.textContent = 'Send to All Users';
    }
}

// Update send button text based on selection
function updateSendButtonText() {
    const recipientType = document.querySelector('input[name="recipient-type"]:checked').value;
    const sendBtnText = document.getElementById('send-btn-text');
    
    if (recipientType === 'all') {
        sendBtnText.textContent = 'Send to All Users';
    } else {
        const selectedCount = document.querySelectorAll('.user-checkbox:checked').length;
        sendBtnText.textContent = selectedCount > 0 
            ? `Send to ${selectedCount} User${selectedCount !== 1 ? 's' : ''}`
            : 'Select Users';
    }
}

// Listen to checkbox changes
document.addEventListener('change', (e) => {
    if (e.target.classList.contains('user-checkbox')) {
        updateSendButtonText();
    }
});

// Toggle all users
function toggleAllUsers() {
    const selectAll = document.getElementById('select-all');
    const checkboxes = document.querySelectorAll('.user-checkbox');
    const visibleCheckboxes = Array.from(checkboxes).filter(cb => 
        cb.closest('.user-item').style.display !== 'none'
    );
    
    visibleCheckboxes.forEach(cb => cb.checked = selectAll.checked);
    updateSendButtonText();
}

// User search
const userSearch = document.getElementById('user-search');
userSearch?.addEventListener('input', () => {
    const searchTerm = userSearch.value.toLowerCase();
    const userItems = document.querySelectorAll('.user-item');
    
    userItems.forEach(item => {
        const name = item.dataset.name;
        const email = item.dataset.email;
        const matches = name.includes(searchTerm) || email.includes(searchTerm);
        item.style.display = matches ? 'flex' : 'none';
    });
});

// Save settings
async function saveSettings() {
    const enabled = document.getElementById('auto-enabled').checked;
    const threshold = document.getElementById('sitting-threshold').value;

    if (!threshold || threshold < 1 || threshold > 300) {
        showToast('Please enter a valid threshold (1-300 minutes)', 'error');
        return;
    }

    try {
        const response = await fetch('/admin/notifications/settings', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            },
            body: JSON.stringify({
                automatic_notifications_enabled: enabled,
                sitting_time_threshold_minutes: parseInt(threshold)
            })
        });

        const data = await response.json();
        
        if (data.success) {
            showToast('Settings saved successfully', 'success');
        } else {
            showToast('Failed to save settings', 'error');
        }
    } catch (error) {
        console.error('Error saving settings:', error);
        showToast('An error occurred', 'error');
    }
}

// Send manual notification
async function sendNotification() {
    const title = document.getElementById('notif-title').value.trim();
    const message = document.getElementById('notif-message').value.trim();
    const recipientType = document.querySelector('input[name="recipient-type"]:checked').value;

    if (!title || !message) {
        showToast('Please fill in both title and message', 'error');
        return;
    }

    const payload = {
        title,
        message,
        send_to_all: recipientType === 'all'
    };

    if (recipientType === 'specific') {
        const selectedUsers = Array.from(document.querySelectorAll('.user-checkbox:checked'))
            .map(cb => parseInt(cb.value));
        
        if (selectedUsers.length === 0) {
            showToast('Please select at least one user', 'error');
            return;
        }
        
        payload.user_ids = selectedUsers;
    }

    try {
        const response = await fetch('/admin/notifications/send', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            },
            body: JSON.stringify(payload)
        });

        const data = await response.json();
        
        if (data.success) {
            showToast(data.message, 'success');
            // Clear form
            document.getElementById('notif-title').value = '';
            document.getElementById('notif-message').value = '';
            titleCount.textContent = '0';
            messageCount.textContent = '0';
            document.querySelectorAll('.user-checkbox').forEach(cb => cb.checked = false);
            document.getElementById('select-all').checked = false;
            updateSendButtonText();
        } else {
            showToast('Failed to send notification', 'error');
        }
    } catch (error) {
        console.error('Error sending notification:', error);
        showToast('An error occurred', 'error');
    }
}

// Toast notification
function showToast(message, type = 'success') {
    const toast = document.getElementById('toast');
    toast.textContent = message;
    toast.className = `toast ${type}`;
    
    setTimeout(() => toast.classList.add('show'), 10);
    
    setTimeout(() => {
        toast.classList.remove('show');
    }, 3000);
}
