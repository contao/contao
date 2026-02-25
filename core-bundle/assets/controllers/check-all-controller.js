import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    #start = null;
    #shiftKey = false;
    #keypress;

    static targets = ['source', 'input'];

    initialize() {
        this.#start = null;
        this.#keypress = (event) => {
            this.#shiftKey = event.shiftKey;
            this.element.style['user-select'] = event.shiftKey ? 'none' : '';
            this.element.style['-webkit-user-select'] = event.shiftKey ? 'none' : '';
        };
    }

    connect() {
        document.addEventListener('keydown', this.#keypress);
        document.addEventListener('keyup', this.#keypress);
    }

    disconnect() {
        document.removeEventListener('keydown', this.#keypress);
        document.removeEventListener('keyup', this.#keypress);
    }

    toggleInput(event) {
        let input = event.target;
        let rowClick = false;

        if (input.tagName !== 'INPUT' && null === input.closest('button, a')) {
            input = event.currentTarget.querySelector(`[data-${this.identifier}-target="input"]`);
            rowClick = true;
        }

        if (!input || input.disabled) {
            return;
        }

        if (input.type === 'radio' && rowClick) {
            input.checked = true;
        } else if (input.type === 'checkbox') {
            if (rowClick) {
                input.checked = !input.checked;
            }

            if (this.#shiftKey && this.#start) {
                this.#shiftToggle(input);
            }

            this.#start = input;
        }
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
