import { Controller } from '@hotwired/stimulus';

/*
 * This controller intercepts form submission to provide immediate feedback on missing required fields.
 * It handles fields hidden in tabs by automatically switching to the correct tab.
 */
export default class extends Controller {
    static targets = ['errorContainer'];

    connect() {
        // Ensure browser's default validation UI doesn't interfere too much, 
        // effectively disabling default bubble but keeping the API working
        this.element.noValidate = true;
    }

    validate(event) {
        let firstInvalidField = null;
        const invalidFields = [];

        // Check all form elements for validity
        // We use Array.from to iterate over form.elements
        const formElements = Array.from(this.element.elements);

        formElements.forEach((field) => {
            // Skip buttons, fieldsets, etc., and fields that don't have constraints
            if (!field.willValidate) return;

            if (!field.checkValidity()) {
                if (!firstInvalidField) {
                    firstInvalidField = field;
                }
                
                // Get the label text for a better error message
                const label = this.findLabel(field);
                const fieldName = label ? label.innerText.replace('*', '').trim() : field.name;
                
                invalidFields.push(fieldName);
                
                // Add invalid class to field for visual feedback (optional, Tailwind styles usually handle :invalid)
                field.classList.add('border-red-500', 'ring-red-500');
                
                // Remove invalid styling on input
                field.addEventListener('input', () => {
                    if (field.checkValidity()) {
                         field.classList.remove('border-red-500', 'ring-red-500');
                    }
                }, { once: true });
            }
        });

        if (firstInvalidField) {
            event.preventDefault();
            this.showErrors(invalidFields);
            this.revealField(firstInvalidField);
            // Focus after revealing
            setTimeout(() => firstInvalidField.focus(), 100);
        }
    }

    findLabel(field) {
        // Try to find label by id
        if (field.id) {
            const label = document.querySelector(`label[for="${field.id}"]`);
            if (label) return label;
        }
        // Try mapped labels for radio/checkbox groups
        return field.closest('div')?.querySelector('label');
    }

    revealField(field) {
        // Find the closest tab content container
        const tabPanel = field.closest('[data-tabs-target="panel"]');
        if (tabPanel) {
            const tabValue = tabPanel.dataset.tabValue;
            
            // Find the controller element (which should be the one holding the tabs logic)
            // Assuming tabs controller is on a parent or the same element
            // We can dispatch an event or trigger a click on the tab button
            
            const tabButton = document.querySelector(`[data-tab-value="${tabValue}"]`);
            if (tabButton) {
                tabButton.click();
            }
        }
    }

    showErrors(fields) {
        // Remove existing error container if any
        if (this.hasErrorContainerTarget) {
            this.errorContainerTarget.remove();
        }
        
        const existingAlert = this.element.querySelector('.form-validation-alert');
        if (existingAlert) existingAlert.remove();

        // Create alert
        const alertHtml = `
            <div class="form-validation-alert mb-6 rounded-md bg-red-50 p-4 border border-red-200">
                <div class="flex">
                    <div class="shrink-0">
                        <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.28 7.22a.75.75 0 00-1.06 1.06L8.94 10l-1.72 1.72a.75.75 0 101.06 1.06L10 11.06l1.72 1.72a.75.75 0 101.06-1.06L11.06 10l1.72-1.72a.75.75 0 00-1.06-1.06L10 8.94 8.28 7.22z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-red-800">
                            Please correct the errors below
                        </h3>
                        <div class="mt-2 text-sm text-red-700">
                            <ul role="list" class="list-disc space-y-1 pl-5">
                                ${fields.map(field => `<li>${field} is required/invalid</li>`).join('')}
                            </ul>
                        </div>
                    </div>
                    <div class="ml-auto pl-3">
                        <div class="-mx-1.5 -my-1.5">
                            <button type="button" class="inline-flex rounded-md bg-red-50 p-1.5 text-red-500 hover:bg-red-100 focus:outline-none focus:ring-2 focus:ring-red-600 focus:ring-offset-2 focus:ring-offset-red-50" onclick="this.closest('.form-validation-alert').remove()">
                                <span class="sr-only">Dismiss</span>
                                <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                <path d="M6.28 5.22a.75.75 0 00-1.06 1.06L8.94 10l-1.72 1.72a.75.75 0 101.06 1.06L10 11.06l1.72 1.72a.75.75 0 101.06-1.06L11.06 10l1.72-1.72a.75.75 0 00-1.06-1.06L10 8.94 8.28 7.22z" />
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        `;

        // Insert at top of form
        this.element.insertAdjacentHTML('afterbegin', alertHtml);
        
        // Scroll to top
        this.element.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
}
