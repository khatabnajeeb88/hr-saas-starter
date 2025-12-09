import Alpine from 'alpinejs';
import collapse from '@alpinejs/collapse';
import dashboardLayout from './components/dashboardLayout.js';
import dropdown from './components/dropdown.js';
import notifications from './components/notifications.js';

Alpine.plugin(collapse);

Alpine.data('dashboardLayout', dashboardLayout);
Alpine.data('dropdown', dropdown);
Alpine.data('notifications', notifications);

window.Alpine = Alpine;
Alpine.start();
