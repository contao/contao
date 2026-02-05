import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['input'];

    delete() {
        for (const input of this.inputTargets) {
            input.value = '';
        }
    }
}
