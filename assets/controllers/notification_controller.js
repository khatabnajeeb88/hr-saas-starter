import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static values = {
        mercureUrl: String,
        topics: Array,
    }

    connect() {
        if (!this.mercureUrlValue) {
            console.error('Mercure URL is missing');
            return;
        }

        const url = new URL(this.mercureUrlValue);
        this.topicsValue.forEach(topic => url.searchParams.append('topic', topic));

        this.eventSource = new EventSource(url.toString(), { withCredentials: true });
        this.eventSource.onmessage = (event) => {
            const data = JSON.parse(event.data);
            this.notify(data);
        }
    }

    disconnect() {
        if (this.eventSource) {
            this.eventSource.close();
        }
    }

    notify(data) {
        // Simple alert for now, can be replaced with a proper toast
        console.log('New Notification:', data);
        
        // Create a simple toast element
        const toast = document.createElement('div');
        toast.className = 'fixed top-4 right-4 bg-blue-500 text-white p-4 rounded shadow-lg z-50 transition-opacity duration-500';
        toast.innerText = data.type + ': ' + JSON.stringify(data.data);
        
        document.body.appendChild(toast);
        
        // Dispatch event for Alpine.js components
        window.dispatchEvent(new CustomEvent('notification:received', { detail: data }));

        // Remove after 3 seconds
        setTimeout(() => {
            toast.style.opacity = '0';
            setTimeout(() => toast.remove(), 500);
        }, 3000);
    }
}
