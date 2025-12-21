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
        if (event) event.preventDefault();
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

    archive(event) {
        event.preventDefault();
        this.submitBulkAction(event.target.dataset.url, null);
    }

    unarchive(event) {
        event.preventDefault();
        this.submitBulkAction(event.target.dataset.url, null);
    }

    delete(event) {
        event.preventDefault();
        const confirmMessage = event.target.dataset.confirm || 'Are you sure?';
        this.submitBulkAction(event.target.dataset.url, confirmMessage, true);
    }

    submitBulkAction(url, confirmMessage = null, isDelete = false) {
        this.closeDropdown();
        
        const checkedRows = this.rowTargets.filter(checkbox => checkbox.checked);
        const ids = checkedRows.map(checkbox => checkbox.value);

        if (!this.isAllSelected && ids.length === 0) {
            alert('Please select at least one item.');
            return;
        }

        if (confirmMessage && !confirm(confirmMessage)) {
            return;
        }

        const form = document.createElement('form');
        form.method = 'POST';
        form.action = url;
        form.style.display = 'none';

        const idsInput = document.createElement('input');
        idsInput.type = 'hidden';
        idsInput.name = 'ids';
        idsInput.value = ids.join(',');
        form.appendChild(idsInput);

        const includeAllInput = document.createElement('input');
        includeAllInput.type = 'hidden';
        includeAllInput.name = 'include_all';
        includeAllInput.value = this.isAllSelected ? '1' : '0';
        form.appendChild(includeAllInput);

        if (isDelete) {
            // Check for existing csrf token in a meta tag or similar if available, 
            // but since we are submitting a form, we might need to inject it.
            // For now, let's assume the controller can handle it if we pass it, 
            // or we might need to render it in a data attribute.
            // Let's look for a global CSRF token if present
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
            if (csrfToken) {
                 const tokenInput = document.createElement('input');
                 tokenInput.type = 'hidden';
                 tokenInput.name = '_token';
                 tokenInput.value = csrfToken;
                 form.appendChild(tokenInput);
            }
        }

        document.body.appendChild(form);
        form.submit();
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
