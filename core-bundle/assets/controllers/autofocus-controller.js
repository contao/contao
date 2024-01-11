import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['input']

    connect () {
        this.inputTargets.every(input => {
            if (input.disabled || input.readonly) {
                return true;
            }

            if (!input.offsetWidth || !input.offsetHeight) {
                return true;
            }

            if (input.closest('.chzn-search')) {
                return true;
            }

            if (input.autocomplete && input.autocomplete !== 'off') {
                return true;
            }

            input.focus();

            return false;
        })
    }
}
