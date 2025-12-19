import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = [
        'list', 
        'empty', 
        'loading', 
        'createModal', 
        'successModal', 
        'newToken', 
        'description', 
        'template',
        'copyButton',
        'copyIcon',
        'checkIcon',
        'copyText'
    ];
    
    static values = {
        listUrl: String,
        createUrl: String,
        deleteUrl: String, // Base URL for deletion, we'll append ID
        copiedText: String,
        copyText: String
    }

    connect() {
        this.fetchTokens();
    }

    async fetchTokens() {
        this.loadingTarget.classList.remove('hidden');
        this.emptyTarget.classList.add('hidden');
        this.listTarget.innerHTML = '';

        try {
            const response = await fetch(this.listUrlValue);
            const tokens = await response.json();
            
            this.loadingTarget.classList.add('hidden');
            this.renderTokens(tokens);
        } catch (error) {
            console.error('Error fetching tokens:', error);
            this.loadingTarget.classList.add('hidden');
        }
    }

    renderTokens(tokens) {
        if (tokens.length === 0) {
            this.emptyTarget.classList.remove('hidden');
            return;
        }

        this.emptyTarget.classList.add('hidden');
        
        tokens.forEach(token => {
            const clone = this.templateTarget.content.cloneNode(true);
            
            clone.querySelector('[data-token-description]').textContent = token.description;
            clone.querySelector('[data-token-masked]').textContent = token.maskedToken;
            clone.querySelector('[data-token-created]').textContent = new Date(token.createdAt).toLocaleDateString();
            
            const lastUsedEl = clone.querySelector('[data-token-last-used-container]');
            if (token.lastUsedAt) {
                clone.querySelector('[data-token-last-used]').textContent = new Date(token.lastUsedAt).toLocaleDateString();
                lastUsedEl.classList.remove('hidden');
            } else {
                 lastUsedEl.classList.add('hidden');
            }

            const deleteBtn = clone.querySelector('[data-action="click->api-tokens#revoke"]');
            deleteBtn.dataset.id = token.id;

            this.listTarget.appendChild(clone);
        });
    }

    openCreateModal() {
        this.createModalTarget.style.display = 'flex';
        this.descriptionTarget.focus();
    }

    closeCreateModal() {
        this.createModalTarget.style.display = 'none';
        this.descriptionTarget.value = '';
    }

    async create(event) {
        event.preventDefault();
        const description = this.descriptionTarget.value;
        if (!description) return;

        try {
            const response = await fetch(this.createUrlValue, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ description })
            });

            if (response.ok) {
                const data = await response.json();
                
                // Show success modal
                this.closeCreateModal();
                this.newTokenTarget.textContent = data.token;
                this.successModalTarget.style.display = 'flex';
                
                // Refresh list (or append efficiently, but refresh is easier)
                this.fetchTokens(); 
            }
        } catch (error) {
            console.error('Error creating token:', error);
        }
    }

    closeSuccessModal() {
        this.successModalTarget.style.display = 'none';
        this.newTokenTarget.textContent = '';
        this.resetCopyButton();
    }

    async revoke(event) {
        if (!confirm(event.target.dataset.confirm)) return;
        
        const id = event.target.dataset.id;
        // Construct delete URL. Assuming basic /profile/api-tokens/{id} structure 
        // or we can use the value if provided.
        // The user's previous code used `/profile/api-tokens/${id}`
        
        try {
            await fetch(`/profile/api-tokens/${id}`, { method: 'DELETE' });
            // Remove element from DOM
            event.target.closest('[data-token-item]').remove();
            
            // Check if empty
            if (this.listTarget.children.length === 0) {
                this.emptyTarget.classList.remove('hidden');
            }
        } catch (error) {
             console.error('Error deleting token:', error);
        }
    }

    copyToken() {
        const token = this.newTokenTarget.textContent;
        navigator.clipboard.writeText(token);
        
        // Show copied state
        this.copyIconTarget.classList.add('hidden');
        this.checkIconTarget.classList.remove('hidden');
        this.copyTextTarget.textContent = this.copiedTextValue;
        
        this.copyButtonTarget.classList.remove('bg-indigo-600', 'hover:bg-indigo-700');
        this.copyButtonTarget.classList.add('bg-green-600', 'hover:bg-green-700');

        setTimeout(() => {
            this.resetCopyButton();
        }, 2000);
    }

    resetCopyButton() {
        this.copyIconTarget.classList.remove('hidden');
        this.checkIconTarget.classList.add('hidden');
        this.copyTextTarget.textContent = this.copyTextValue;
        
        this.copyButtonTarget.classList.add('bg-indigo-600', 'hover:bg-indigo-700');
        this.copyButtonTarget.classList.remove('bg-green-600', 'hover:bg-green-700');
    }
}
