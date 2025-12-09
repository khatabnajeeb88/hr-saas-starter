export default (config) => ({
    open: false,
    notifications: [],
    loading: false,
    hasUnread: false,
    selectedAnnouncement: null,
    urlList: config.urlList,
    urlMarkAllRead: config.urlMarkAllRead,

    async fetchNotifications() {
        this.loading = true;
        try {
            const response = await fetch(this.urlList);
            this.notifications = await response.json();
            this.hasUnread = this.notifications.some(n => !n.isRead);
        } catch (error) {
            console.error('Error fetching notifications:', error);
        } finally {
            this.loading = false;
        }
    },

    async markAsRead(id) {
        try {
            // Optimistic update
            const notification = this.notifications.find(n => n.id === id);
            if (notification) notification.isRead = true;
            this.hasUnread = this.notifications.some(n => !n.isRead);

            // Calculate the correct URL
            const url = this.urlList + '/' + id + '/read';

            await fetch(url, { method: 'POST' });
        } catch (error) {
            console.error('Error marking as read:', error);
        }
    },

    async markAllAsRead() {
        try {
            // Optimistic update
            this.notifications.forEach(n => n.isRead = true);
            this.hasUnread = false;

            await fetch(this.urlMarkAllRead, { method: 'POST' });
        } catch (error) {
            console.error('Error marking all as read:', error);
        }
    },

    handleNotificationClick(notification) {
        // Always mark as read
        if (!notification.isRead) {
            this.markAsRead(notification.id);
        }

        // If it is an announcement, open modal
        if (notification.data.is_announcement) {
            this.selectedAnnouncement = notification;
            this.open = false; // Close dropdown
        }
    },

    init() {
        window.addEventListener('notification:received', (event) => {
            const data = event.detail;
            // Assuming data matches the structure, or we might need to fetch fresh list.
            // For simplicity/accuracy, let's fetch fresh list or prepend if structure matches.
            // The Mercure payload might be just the data, not the full DB record structure used in the list.
            // Let's re-fetch for consistency for now, or construct a fake object.
            
            // If we re-fetch:
            this.fetchNotifications();
            this.hasUnread = true;
        });
        
        this.fetchNotifications();
    },

    toggle() {
        this.open = !this.open;
        if (this.open) {
            this.fetchNotifications();
        }
    },
    
    close() {
        this.open = false;
    }
});
