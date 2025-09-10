import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    #start = null;

    static targets = ['source', 'input'];

    initialize() {
        this.#start = null;
    }

    toggleInput(event) {
        const input = event.target;

        if (event.shiftKey && null !== this.#start) {
            this.#shiftToggle(input);
        }

        this.#start = input;
    }

    toggleAll(event) {
        const checked = event.target.checked;

        for (const el of this.inputTargets) {
            el.checked = checked;
        }
    }

    #shiftToggle(el) {
        const thisIndex = this.inputTargets.indexOf(el);
        const startIndex = this.inputTargets.indexOf(this.#start);

        const from = Math.min(thisIndex, startIndex);
        const to = Math.max(thisIndex, startIndex);
        const status = this.#start.checked;

        for (let i = from; i <= to; i++) {
            this.inputTargets[i].checked = status;
        }
    }
}
