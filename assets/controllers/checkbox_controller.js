import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['row', 'header', 'count'];
    static values = { message: String, totalCount: Number };

    connect() {
        console.log('Checkbox controller connected');
        this.isAllSelected = false;
        this.updateHeaderState();
    }

    selectVisible() {
        this.isAllSelected = false;
        this.rowTargets.forEach(checkbox => {
            checkbox.checked = true;
        });
        this.updateHeaderState();
        this.closeDropdown();
    }

    selectAll() {
        // Logically select all (frontend only for now)
        this.selectVisible(); // Visually check all on page
        this.isAllSelected = true;
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
        this.isAllSelected = false;
        this.rowTargets.forEach(checkbox => {
            checkbox.checked = false;
        });
        this.updateHeaderState();
        this.closeDropdown();
    }

    export(event) {
        console.log('Export action triggered');
        event.preventDefault();
        this.closeDropdown();

        const checkedRows = this.rowTargets.filter(checkbox => checkbox.checked);
        const ids = checkedRows.map(checkbox => checkbox.value);
        
        const idsInput = document.getElementById('export_ids');
        const includeAllInput = document.getElementById('export_include_all');
        const modal = document.getElementById('export_modal');

        console.log('Export elements check:', {
            idsInput: !!idsInput,
            includeAllInput: !!includeAllInput,
            modal: !!modal,
            modalElement: modal
        });

        if (idsInput && includeAllInput && modal) {
            idsInput.value = ids.join(',');
            
            // Export all if:
            // 1. "Select All" (global) was clicked (isAllSelected is true)
            // 2. OR No specific rows are selected (User requirement: "If no record is selected then export all")
            if (this.isAllSelected || ids.length === 0) {
                includeAllInput.value = '1';
                // If we are exporting all because none are selected, clear manual IDs to be safe, 
                // although backend should prioritize include_all if set.
                 if (ids.length === 0) {
                     idsInput.value = ''; 
                 }
            } else {
                includeAllInput.value = '0';
            }

            modal.showModal();
        }
    }

    toggle(event) {
        this.isAllSelected = false;
        this.updateHeaderState();
    }

    updateHeaderState() {
        const hasRows = this.rowTargets.length > 0;
        const allChecked = hasRows && this.rowTargets.every(checkbox => checkbox.checked);
        let checkedCount = this.rowTargets.filter(checkbox => checkbox.checked).length;
        
        if (this.isAllSelected && this.hasTotalCountValue) {
            checkedCount = this.totalCountValue;
        }
        
        console.log('Update Header State:', { 
            rows: this.rowTargets.length, 
            checkedCount,
            allChecked,
            isAllSelected: this.isAllSelected
        });

        if (this.hasHeaderTarget) {
            const headerCheckbox = this.headerTarget.querySelector('input[type="checkbox"]');
            if (headerCheckbox) {
                headerCheckbox.checked = allChecked;
                headerCheckbox.indeterminate = false;
            }
        }

        if (this.hasCountTarget) {
            if (checkedCount > 0) {
                this.countTarget.textContent = this.messageValue.replace('%count%', checkedCount);
                this.countTarget.classList.remove('hidden');
            } else {
                this.countTarget.classList.add('hidden');
            }
        }
    }
}
