import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    #start = null;
    #shiftKey = false;
    #keypress;

    static targets = ['source', 'input'];

    static afterLoad(identifier, application) {
        const addAttribute = (el, attribute, value) => {
            if (!el) {
                return false;
            }

            const values = (el.getAttribute(attribute) || '').split(' ');

            if (values.includes(value)) {
                return false;
            }

            values.push(value);

            el.setAttribute(attribute, values.join(' ').trim());

            return true;
        };

        const setupController = () => {
            if (!addAttribute(document.getElementById('tl_listing'), 'data-controller', identifier)) {
                return;
            }

            addAttribute(document.getElementById('tl_select_trigger'), 'data-action', `${identifier}#toggleAll`);

            for (const el of document.querySelectorAll('.tl_listing .tl_tree_checkbox')) {
                addAttribute(el, `data-${identifier}-target`, 'input');
                addAttribute(el, 'data-action', `click->${identifier}#toggleInput`);
            }
        };

        document.addEventListener('DOMContentLoaded', setupController);
        document.addEventListener('ajax_change', setupController);
        document.addEventListener('turbo:render', setupController);
        document.addEventListener('turbo:frame-render', setupController);
        setupController();

        Backend.enableToggleSelect = () => {
            if (window.console) {
                console.warn(
                    'Using Backend.enableToggleSelect() is deprecated and will be removed in Contao 6. Apply the Stimulus actions instead.',
                );
            }

            setupController();
        };
    }

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

        if (input.tagName !== 'INPUT') {
            input = event.currentTarget.querySelector(`[data-${this.identifier}-target="input"]`);
            rowClick = true;
        }

        if (!input) {
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
