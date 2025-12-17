import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ["collection", "template"];

    static values = {
        index: Number,
        prototype: String,
    }

    connect() {
        this.indexValue = this.collectionTarget.children.length;
    }

    addCollectionElement(event) {
        event.preventDefault();

        const item = this.prototypeValue.replace(/__name__/g, this.indexValue);
        this.collectionTarget.insertAdjacentHTML('beforeend', item);
        this.indexValue++;
    }

    removeCollectionElement(event) {
        event.preventDefault();
        const item = event.target.closest('.collection-item');
        item.remove();
    }
}
