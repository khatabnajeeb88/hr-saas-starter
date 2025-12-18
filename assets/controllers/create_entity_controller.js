import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ["input", "status", "modal"];
    static values = {
        createUrl: String,
        selectId: String
    }

    connect() {
        console.log('Create Entity Controller connected');
    }

    async create(event) {
        console.log('Create clicked');
        event.preventDefault();
        
        const input = this.inputTarget;
        const name = input.value;
        const button = event.currentTarget;
        const originalText = button.innerHTML;
        const url = this.createUrlValue;

        if (!name) {
            // Simple validation feedback could be improved
            input.classList.add('is-invalid');
            return;
        }

        input.classList.remove('is-invalid');
        button.disabled = true;
        button.innerHTML = '<span class="loading loading-spinner text-primary"></span> Creating...';

        try {
            const response = await fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({ name: name })
            });

            const data = await response.json();

            if (response.ok) {
                this.updateSelect(data.id, data.name);
                this.closeModal();
                input.value = '';
                // Optional: Show success notification
            } else {
                console.error('Error creating entity:', data.error);
                // Optional: Show error notification
            }
        } catch (error) {
            console.error('Network error:', error);
        } finally {
            button.disabled = false;
            button.innerHTML = originalText;
        }
    }

    updateSelect(id, name) {
        if (!this.hasSelectIdValue) {
             console.error('No select ID provided');
             return;
        }
        const select = document.querySelector(`#${this.selectIdValue}`);
        if (select) {
            const option = new Option(name, id, true, true);
            select.add(option);
            select.dispatchEvent(new Event('change'));
        }
    }

    closeModal() {
        if (this.element.tagName === 'DIALOG') {
            this.element.close();
        } else {
             // Fallback or previous logic if needed, but for now we assume DIALOG
             // If we need to support non-dialog, we might need a different approach
             this.element.classList.add('hidden'); // Simple fallback?
        }
    }
}
