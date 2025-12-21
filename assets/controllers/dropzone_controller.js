import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['input', 'filename', 'container'];

    connect() {
        this.inputTarget.addEventListener('change', this.handleFileSelect.bind(this));
        
        // Prevent default behavior for drag events
        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
            this.containerTarget.addEventListener(eventName, this.preventDefaults, false);
        });

        // Highlight drop area when item is dragged over it
        ['dragenter', 'dragover'].forEach(eventName => {
            this.containerTarget.addEventListener(eventName, this.highlight.bind(this), false);
        });

        ['dragleave', 'drop'].forEach(eventName => {
            this.containerTarget.addEventListener(eventName, this.unhighlight.bind(this), false);
        });

        // Handle dropped files
        this.containerTarget.addEventListener('drop', this.handleDrop.bind(this), false);
    }

    preventDefaults(e) {
        e.preventDefault();
        e.stopPropagation();
    }

    highlight() {
        this.containerTarget.classList.add('border-indigo-500', 'bg-indigo-50', 'dark:bg-indigo-900/10');
    }

    unhighlight() {
        this.containerTarget.classList.remove('border-indigo-500', 'bg-indigo-50', 'dark:bg-indigo-900/10');
    }

    handleDrop(e) {
        const dt = e.dataTransfer;
        const files = dt.files;
        this.handleFiles(files);
    }

    handleFiles(files) {
        if (files.length > 0) {
            this.inputTarget.files = files;
            this.updateFilename(files[0].name);
        }
    }

    handleFileSelect(e) {
        const files = e.target.files;
        if (files.length > 0) {
            this.updateFilename(files[0].name);
        }
    }

    updateFilename(name) {
        if (this.hasFilenameTarget) {
            this.filenameTarget.textContent = name;
            this.filenameTarget.classList.remove('hidden');
        }
    }

    trigger() {
        this.inputTarget.click();
    }
}
