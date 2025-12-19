import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['input', 'preview', 'placeholder'];

    connect() {
        // Check if there's initially an image (e.g. edit mode) logic if needed
    }

    update(event) {
        const file = event.target.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = (e) => {
                this.previewTarget.src = e.target.result;
                this.previewTarget.classList.remove('hidden');
                if (this.hasPlaceholderTarget) {
                    this.placeholderTarget.classList.add('hidden');
                }
            };
            reader.readAsDataURL(file);
        } else {
            this.previewTarget.src = '';
            this.previewTarget.classList.add('hidden');
             if (this.hasPlaceholderTarget) {
                this.placeholderTarget.classList.remove('hidden');
            }
        }
    }
}
