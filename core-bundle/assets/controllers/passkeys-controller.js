import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['form', 'nameInput'];

    nameInputTargetConnected(el) {
        el.focus();
        el.select();
    }

    cancelEdit() {
        this.nameInputTarget.value = this.nameInputTarget.getAttribute('value');
        this.formTarget.requestSubmit();
    }
}
