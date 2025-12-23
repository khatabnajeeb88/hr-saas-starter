import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['list', 'count', 'markAllRead'];
    static values = {
        urlList: String,
        urlMarkAllRead: String,
    }

    connect() {
        this.fetchNotifications();
    }

    async open() {
        // Already fetched on connect, but can refresh if needed
        // await this.fetchNotifications();
    }

    async fetchNotifications() {
        if (!this.urlListValue) return;

        try {
            const response = await fetch(this.urlListValue);
            const data = await response.json();
            this.renderNotifications(data);
        } catch (error) {
            console.error('Error fetching notifications:', error);
            this.listTarget.innerHTML = `<div class="p-4 text-center text-red-500 text-sm">Failed to load notifications</div>`;
        }
    }

    renderNotifications(notifications) {
        if (notifications.length === 0) {
            this.listTarget.innerHTML = `<div class="p-4 text-center text-slate-500 text-sm">No new notifications</div>`;
            return;
        }

        this.listTarget.innerHTML = notifications.map(n => this.buildNotificationHtml(n)).join('');
        this.updateUnreadState(notifications);
    }
    
    onNotificationReceived(event) {
        const notification = event.detail;
        
        // Remove "No new notifications" message if present
        if (this.listTarget.querySelector('.text-center')) {
            this.listTarget.innerHTML = '';
        }

        // Prepend new notification
        const html = this.buildNotificationHtml(notification);
        this.listTarget.insertAdjacentHTML('afterbegin', html);
        
        this.updateUnreadState([notification]); // Just ensures count/badge is updated if logic added there
        
        // Show count badge
        if (this.hasCountTarget) {
            this.countTarget.style.display = 'block';
        }
    }

    updateUnreadState(notifications) {
        // Show "Mark all read" if there are unread items
        if (notifications.some(n => !n.isRead)) {
            if (this.hasMarkAllReadTarget) this.markAllReadTarget.style.display = 'block';
             if (this.hasCountTarget) this.countTarget.style.display = 'block';
        }
    }

    buildNotificationHtml(notification) {
        // Basic template, customize as needed
        const iconInfo = `<div class="w-8 h-8 rounded-full bg-blue-100 text-blue-600 flex items-center justify-center shrink-0"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg></div>`;
        const iconWarning = `<div class="w-8 h-8 rounded-full bg-yellow-100 text-yellow-600 flex items-center justify-center shrink-0"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg></div>`;
        
        const icon = notification.type === 'warning' || notification.type === 'danger' ? iconWarning : iconInfo;
        const message = notification.data.message || 'New notification';
        const time = new Date(notification.createdAt).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
        
        return `
            <div class="px-4 py-3 hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors border-b border-slate-100 dark:border-slate-700 last:border-0 cursor-pointer ${notification.isRead ? 'opacity-60' : ''}">
                <div class="flex gap-3">
                    ${icon}
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium text-slate-900 dark:text-slate-100 truncate">${message}</p>
                        <p class="text-xs text-slate-500 mt-0.5">${time}</p>
                    </div>
                    ${!notification.isRead ? '<div class="w-2 h-2 bg-purple-500 rounded-full mt-1.5 shrink-0"></div>' : ''}
                </div>
            </div>
        `;
    }

    async markAllRead() {
        if (!this.urlMarkAllReadValue) return;

        try {
            await fetch(this.urlMarkAllReadValue, { method: 'POST' });
            if (this.hasMarkAllReadTarget) this.markAllReadTarget.style.display = 'none';
            // Mark all items as read visually
            const dots = this.listTarget.querySelectorAll('.bg-purple-500');
            dots.forEach(dot => dot.remove());
            const items = this.listTarget.querySelectorAll('.cursor-pointer');
            items.forEach(item => item.classList.add('opacity-60'));
            
            // Hide bell badge (optional, if managed here)
             if (this.hasCountTarget) {
                this.countTarget.style.display = 'none';
            }
        } catch (error) {
            console.error('Error marking all as read:', error);
        }
    }
}
