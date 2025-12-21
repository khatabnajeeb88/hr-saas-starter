import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['row', 'header'];

    connect() {
        this.updateHeaderState();
    }

    selectVisible() {
        this.rowTargets.forEach(checkbox => {
            checkbox.checked = true;
        });
        this.updateHeaderState();
        this.closeDropdown();
    }

    selectAll() {
        // Logically select all (frontend only for now)
        this.selectVisible();
        // Here you would typically set a hidden input or state to indicate "all pages selected"
        // for subsequent form submissions.
        this.updateHeaderState();
        this.closeDropdown();
    }

    closeDropdown() {
        if (this.hasHeaderTarget) {
            const dropdown = this.headerTarget; 
            // In DaisyUI (CSS focus-based), removing focus closes it.
            // If the trigger (label or its child) has focus, blurring it works.
            if (document.activeElement && dropdown.contains(document.activeElement)) {
                document.activeElement.blur();
            }
        }
    }

    deselectAll() {
        this.rowTargets.forEach(checkbox => {
            checkbox.checked = false;
        });
        this.updateHeaderState();
        this.closeDropdown();
    }

    toggle(event) {
        this.updateHeaderState();
    }

    updateHeaderState() {
        const hasRows = this.rowTargets.length > 0;
        const allChecked = hasRows && this.rowTargets.every(checkbox => checkbox.checked);
        
        console.log('Update Header State:', { 
            rows: this.rowTargets.length, 
            allChecked, 
            firstChecked: this.rowTargets[0]?.checked 
        });

        if (this.hasHeaderTarget) {
            const headerCheckbox = this.headerTarget.querySelector('input[type="checkbox"]');
            if (headerCheckbox) {
                headerCheckbox.checked = allChecked;
                headerCheckbox.indeterminate = false;
            }
        }
    }
}
