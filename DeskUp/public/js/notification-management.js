/**
 * Notification Management for Admin Users
 * Handles notification settings and manual notification sending
 */

// Get CSRF token
const getCsrfToken = () => document.querySelector('meta[name="csrf-token"]')?.content;

// ============================================
// NOTIFICATION SETTINGS MANAGEMENT
// ============================================

class NotificationSettings {
    constructor() {
        this.saveBtn = document.getElementById('saveSettingsBtn');
        this.autoToggle = document.getElementById('autoNotificationsToggle');
        this.thresholdInput = document.getElementById('sittingThreshold');
        this.messageEl = document.getElementById('settingsMessage');
        
        if (this.saveBtn) {
            this.init();
        }
    }

    init() {
        // Load settings from localStorage on page load
        this.loadSavedSettings();
        
        // Save settings button
        this.saveBtn.addEventListener('click', () => this.saveSettings());
    }

    loadSavedSettings() {
        if (!this.autoToggle || !this.thresholdInput) return;
        
        const savedSettings = localStorage.getItem('notificationSettings');
        if (savedSettings) {
            try {
                const settings = JSON.parse(savedSettings);
                this.autoToggle.checked = settings.automatic_notifications_enabled;
                this.thresholdInput.value = settings.sitting_time_threshold_minutes;
            } catch (error) {
                console.error('Error loading saved settings:', error);
            }
        }
    }

    async saveSettings() {
        const settingsData = {
            automatic_notifications_enabled: this.autoToggle.checked,
            sitting_time_threshold_minutes: parseInt(this.thresholdInput.value),
        };

        try {
            const response = await fetch('/api/notifications/settings', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': getCsrfToken(),
                },
                body: JSON.stringify(settingsData),
            });

            const data = await response.json();

            if (data.success) {
                // Save to localStorage
                localStorage.setItem('notificationSettings', JSON.stringify(settingsData));
                this.showMessage('Settings saved successfully!', 'success');
            } else {
                this.showMessage(data.message || 'Failed to save settings', 'error');
            }
        } catch (error) {
            console.error('Error saving settings:', error);
            this.showMessage('An error occurred while saving settings', 'error');
        }
    }

    showMessage(text, type) {
        if (!this.messageEl) return;
        
        this.messageEl.textContent = text;
        this.messageEl.className = `message ${type}`;
        this.messageEl.style.display = 'block';
        
        setTimeout(() => {
            this.messageEl.style.display = 'none';
        }, 5000);
    }
}

// ============================================
// MANUAL NOTIFICATION SENDING
// ============================================

class ManualNotificationSender {
    constructor() {
        this.form = document.getElementById('manualNotificationForm');
        this.sendToAllCheckbox = document.getElementById('sendToAll');
        this.userSelectGroup = document.getElementById('userSelectGroup');
        this.messageEl = document.getElementById('notificationFormMessage');
        this.charCount = document.getElementById('charCount');
        this.messageTextarea = document.getElementById('notificationMessage');
        
        if (this.form) {
            this.init();
        }
    }

    init() {
        // Character counter
        if (this.messageTextarea && this.charCount) {
            this.messageTextarea.addEventListener('input', (e) => {
                this.charCount.textContent = e.target.value.length;
            });
        }

        // Toggle user selection based on "send to all"
        if (this.sendToAllCheckbox && this.userSelectGroup) {
            this.sendToAllCheckbox.addEventListener('change', () => {
                this.toggleUserSelection();
            });
        }

        // Form submission
        this.form.addEventListener('submit', (e) => this.handleSubmit(e));
    }

    toggleUserSelection() {
        this.userSelectGroup.style.display = this.sendToAllCheckbox.checked ? 'none' : 'block';
        
        // Uncheck all user checkboxes when "send to all" is checked
        if (this.sendToAllCheckbox.checked) {
            document.querySelectorAll('input[name="user_ids[]"]').forEach(cb => {
                cb.checked = false;
            });
        }
    }

    async handleSubmit(e) {
        e.preventDefault();

        const formData = new FormData(this.form);
        const data = {
            title: formData.get('title'),
            message: formData.get('message'),
            send_to_all: this.sendToAllCheckbox.checked,
        };

        // Validate user selection
        if (!this.sendToAllCheckbox.checked) {
            const selectedUsers = Array.from(document.querySelectorAll('input[name="user_ids[]"]:checked'))
                .map(cb => parseInt(cb.value));
            
            if (selectedUsers.length === 0) {
                this.showMessage('Please select at least one user or check "Send to all users"', 'error');
                return;
            }
            
            data.user_ids = selectedUsers;
        }

        try {
            const response = await fetch('/api/notifications/send-manual', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': getCsrfToken(),
                },
                body: JSON.stringify(data),
            });

            const result = await response.json();

            if (result.success) {
                this.showMessage(result.message || `Notification sent successfully!`, 'success');
                this.resetForm();
            } else {
                this.showMessage(result.message || 'Failed to send notification', 'error');
            }
        } catch (error) {
            console.error('Error sending notification:', error);
            this.showMessage('An error occurred while sending notification', 'error');
        }
    }

    resetForm() {
        this.form.reset();
        if (this.charCount) this.charCount.textContent = '0';
        if (this.userSelectGroup) this.userSelectGroup.style.display = 'block';
    }

    showMessage(text, type) {
        if (!this.messageEl) return;
        
        this.messageEl.textContent = text;
        this.messageEl.className = `message ${type}`;
        this.messageEl.style.display = 'block';
        
        setTimeout(() => {
            this.messageEl.style.display = 'none';
        }, 5000);
    }
}

// ============================================
// INITIALIZE ON DOM READY
// ============================================

function initNotificationManagement() {
    new NotificationSettings();
    new ManualNotificationSender();
}

// Initialize when DOM is ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initNotificationManagement);
} else {
    initNotificationManagement();
}
