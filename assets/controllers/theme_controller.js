import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ["input", "button"];
    static values = {
        current: String
    }

    connect() {
        this.initializeTheme();
        
        // Listen for system preference changes
        window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', e => {
            if (!('theme' in localStorage)) {
                this.applyTheme('system');
            }
        });
    }

    initializeTheme() {
        // Check if there is a saved theme
        const savedTheme = localStorage.getItem('theme');
        if (savedTheme) {
            this.applyTheme(savedTheme);
        } else {
            this.applyTheme('system');
        }
    }

    setTheme(event) {
        const theme = event.currentTarget.dataset.themeValue;
        this.applyTheme(theme);
    }

    applyTheme(theme) {
        this.currentValue = theme;
        
        if (theme === 'dark') {
            document.documentElement.classList.add('dark');
            localStorage.setItem('theme', 'dark');
        } else if (theme === 'light') {
            document.documentElement.classList.remove('dark');
            localStorage.setItem('theme', 'light');
        } else {
            // System
            localStorage.removeItem('theme');
            if (window.matchMedia('(prefers-color-scheme: dark)').matches) {
                document.documentElement.classList.add('dark');
            } else {
                document.documentElement.classList.remove('dark');
            }
        }

        this.updateActiveState(theme);
    }

    updateActiveState(activeTheme) {
        // Update UI buttons if they exist
        this.buttonTargets.forEach(button => {
            const buttonTheme = button.dataset.themeValue;
            if (buttonTheme === activeTheme) {
                button.classList.add('bg-gray-200', 'dark:bg-gray-700', 'text-gray-900', 'dark:text-white');
                button.classList.remove('text-gray-500', 'dark:text-gray-400', 'hover:text-gray-700', 'dark:hover:text-gray-300');
            } else {
                button.classList.remove('bg-gray-200', 'dark:bg-gray-700', 'text-gray-900', 'dark:text-white');
                button.classList.add('text-gray-500', 'dark:text-gray-400', 'hover:text-gray-700', 'dark:hover:text-gray-300');
            }
        });
    }
}
