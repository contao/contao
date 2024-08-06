import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    connect() {
        this.chosen = new Chosen(this.element);
    }

    disconnect() {
        this.chosen.container.remove();
        this.chosen = null;
    }
}
