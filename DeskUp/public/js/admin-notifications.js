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

    if (!title || !message) {
        showToast('Please fill in both title and message', 'error');
        return;
    }

    try {
        const response = await fetch('/admin/notifications/send', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            },
            body: JSON.stringify({ title, message })
        });

        const data = await response.json();
        
        if (data.success) {
            showToast(data.message, 'success');
            // Clear form
            document.getElementById('notif-title').value = '';
            document.getElementById('notif-message').value = '';
            titleCount.textContent = '0';
            messageCount.textContent = '0';
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
