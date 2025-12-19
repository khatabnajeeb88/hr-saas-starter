import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['tab', 'panel'];
    static classes = ['active'];

    connect() {
        // Show first tab by default if none selected
        if (!this.hasActiveTab) {
            this.showTab(this.tabTargets[0].dataset.tabValue);
        }
    }

    switch(event) {
        event.preventDefault();
        const tabValue = event.currentTarget.dataset.tabValue;
        this.showTab(tabValue);
    }

    showTab(value) {
        this.tabTargets.forEach(tab => {
            if (tab.dataset.tabValue === value) {
                tab.classList.add(...this.activeClasses);
                tab.classList.remove('text-gray-500', 'hover:text-gray-700', 'hover:border-gray-300');
            } else {
                tab.classList.remove(...this.activeClasses);
                tab.classList.add('text-gray-500', 'hover:text-gray-700', 'hover:border-gray-300');
            }
        });

        this.panelTargets.forEach(panel => {
            if (panel.dataset.tabValue === value) {
                panel.classList.remove('hidden');
            } else {
                panel.classList.add('hidden');
            }
        });
    }

    get hasActiveTab() {
        return this.tabTargets.some(tab => tab.classList.contains(this.activeClasses[0]));
    }
}
