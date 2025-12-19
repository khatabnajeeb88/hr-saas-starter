import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['menu', 'button'];
    static values = {
        open: Boolean
    }

    connect() {
        if (this.hasMenuTarget) {
            this.menuTarget.style.display = 'none';
        }
        this.clickOutsideHandler = this.clickOutside.bind(this);
    }

    toggle(event) {
        event.stopPropagation();
        this.openValue = !this.openValue;
    }

    openValueChanged(isOpen) {
        if (!this.hasMenuTarget) return;

        if (isOpen) {
            this.menuTarget.style.display = 'block';
            this.positionMenu();
            document.addEventListener('click', this.clickOutsideHandler);
            document.addEventListener('keydown', this.escapeHandler.bind(this));
        } else {
            this.menuTarget.style.display = 'none';
            document.removeEventListener('click', this.clickOutsideHandler);
        }
    }

    positionMenu() {
        if (!this.hasButtonTarget || !this.hasMenuTarget) return;
        
        // Simple positioning: align right edge of menu with right edge of button
        // Or strictly strictly verify if we need to be a 'popper'
        // For the table, we want it fixed/absolute relative to viewport or body?
        // The previous alpine code used fixed positioning based on rect.
        
        const rect = this.buttonTarget.getBoundingClientRect();
        
        // We will make the menu fixed to break out of table overflow
        this.menuTarget.style.position = 'fixed';
        this.menuTarget.style.top = `${rect.bottom + window.scrollY}px`;
        
        // Align right logic (rtl support needed?)
        // Default to aligning right:
        // left = right - width
        const menuWidth = this.menuTarget.offsetWidth || 192; // fallback width
        
        if (document.dir === 'rtl') {
             this.menuTarget.style.left = `${rect.left}px`;
        } else {
             this.menuTarget.style.left = `${rect.right - menuWidth}px`;
        }
        
        this.menuTarget.style.zIndex = '9999';
    }

    clickOutside(event) {
        if (!this.element.contains(event.target) && !this.menuTarget.contains(event.target)) {
            this.openValue = false;
        }
    }
    
    escapeHandler(event) {
        if (event.key === 'Escape') {
             this.openValue = false;
        }
    }
}
