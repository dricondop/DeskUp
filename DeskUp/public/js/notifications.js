/**
 * DeskUp Notification System
 * Handles popup notifications at bottom-right corner
 */

class NotificationManager {
    constructor() {
        this.notifications = [];
        this.container = null;
        this.checkInterval = 30000; // Check every 30 seconds
        this.init();
    }

    init() {
        // Create notification container
        this.createContainer();
        
        // Start checking for new notifications
        this.startPolling();
        
        // Check immediately on load
        this.checkForNotifications();
    }

    createContainer() {
        this.container = document.createElement('div');
        this.container.id = 'notification-container';
        this.container.className = 'notification-container';
        document.body.appendChild(this.container);
    }

    async checkForNotifications() {
        try {
            const response = await fetch('/api/notifications/pending', {
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
                },
            });

            if (!response.ok) return;

            const notifications = await response.json();
            
            // Show only unread notifications
            const unreadNotifications = notifications.filter(n => !n.is_read);
            
            unreadNotifications.forEach(notification => {
                if (!this.isNotificationShown(notification.id)) {
                    this.showNotification(notification);
                }
            });
        } catch (error) {
            console.error('Error fetching notifications:', error);
        }
    }

    isNotificationShown(id) {
        return this.notifications.includes(id);
    }

    showNotification(notification) {
        // Track that we've shown this notification
        this.notifications.push(notification.id);

        // Create notification element
        const notificationEl = document.createElement('div');
        notificationEl.className = 'notification-popup';
        notificationEl.dataset.id = notification.id;

        // Determine icon based on type
        const icon = notification.type === 'automatic' ? '🧍' : '📢';

        notificationEl.innerHTML = `
            <div class="notification-icon">${icon}</div>
            <div class="notification-content">
                <div class="notification-title">${this.escapeHtml(notification.title)}</div>
                <div class="notification-message">${this.escapeHtml(notification.message)}</div>
                <div class="notification-time">${this.getTimeAgo(notification.created_at)}</div>
            </div>
            <button class="notification-close" aria-label="Close notification">×</button>
        `;

        // Add to container
        this.container.appendChild(notificationEl);

        // Trigger animation
        setTimeout(() => {
            notificationEl.classList.add('show');
        }, 10);

        // Close button handler
        const closeBtn = notificationEl.querySelector('.notification-close');
        closeBtn.addEventListener('click', () => {
            this.closeNotification(notificationEl, notification.id);
        });

        // Auto-close after 10 seconds for automatic notifications
        if (notification.type === 'automatic') {
            setTimeout(() => {
                if (notificationEl.parentElement) {
                    this.closeNotification(notificationEl, notification.id);
                }
            }, 10000);
        }

        // Click to close
        notificationEl.addEventListener('click', (e) => {
            if (e.target.tagName !== 'BUTTON') {
                this.closeNotification(notificationEl, notification.id);
            }
        });
    }

    closeNotification(element, notificationId) {
        element.classList.remove('show');
        element.classList.add('hide');

        setTimeout(() => {
            element.remove();
        }, 300);

        // Mark as read
        this.markAsRead([notificationId]);
    }

    async markAsRead(notificationIds) {
        try {
            await fetch('/api/notifications/mark-read', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
                },
                body: JSON.stringify({ notification_ids: notificationIds }),
            });
        } catch (error) {
            console.error('Error marking notification as read:', error);
        }
    }

    startPolling() {
        setInterval(() => {
            this.checkForNotifications();
        }, this.checkInterval);
    }

    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    getTimeAgo(timestamp) {
        const now = new Date();
        const created = new Date(timestamp);
        const seconds = Math.floor((now - created) / 1000);

        if (seconds < 60) return 'Just now';
        if (seconds < 3600) return `${Math.floor(seconds / 60)}m ago`;
        if (seconds < 86400) return `${Math.floor(seconds / 3600)}h ago`;
        return `${Math.floor(seconds / 86400)}d ago`;
    }
}

// Initialize notification manager when DOM is ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        window.notificationManager = new NotificationManager();
    });
} else {
    window.notificationManager = new NotificationManager();
}
