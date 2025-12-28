import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ["collection"];

    static values = {
        index: Number,
        prototype: String,
    }

    connect() {
        // Initialize index based on current number of items
        this.indexValue = this.collectionTarget.children.length;
    }

    add(event) {
        event.preventDefault();
        
        // Replace __name__ with the current index
        const item = this.prototypeValue.replace(/__name__/g, this.indexValue);
        
        // Insert the new item
        this.collectionTarget.insertAdjacentHTML('beforeend', item);
        
        // Increment index
        this.indexValue++;
    }

    remove(event) {
        event.preventDefault();
        
        // Find the parent container of the item to remove
        // In the template, the item is wrapped in a div with "relative" class
        const item = event.target.closest('.relative');
        if (item) {
            item.remove();
        }
    }
}
