import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['source', 'input'];

    static values = {
        rowGuards: {
            type: Array,
            default: ['input', 'label', 'button', 'a', '.operations'],
        },
    };

    initialize() {
        this.start = null;
    }

    rowClick(event) {
        if (event.target.closest(this.rowGuardsValue)) {
            return;
        }

        if (this.start && event.shiftKey) {
            this.#shiftToggle(this.inputTarget);
            return;
        }

        target.checked ^= 1;
        this.start = target;
    }

    inputClick(event) {
        const input = event.target;

        if (event.shiftKey && null !== this.start) {
            this.#shiftToggle(input);
        }

        this.start = input;
    }

    toggleAll() {
        const checked = this.sourceTarget.checked;

        for (const el of this.inputTargets) {
            el.checked = checked;
        }
    }

    #shiftToggle(el) {
        const thisIndex = this.inputTargets.indexOf(el);
        const startIndex = this.inputTargets.indexOf(this.start);

        const from = Math.min(thisIndex, startIndex);
        const to = Math.max(thisIndex, startIndex);
        const status = this.start.checked;

        for (let i = from; i <= to; i++) {
            this.inputTargets[i].checked = status;
        }
    }
}
