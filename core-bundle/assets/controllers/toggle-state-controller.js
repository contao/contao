import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['button', 'dropdown'];

    static values = {
        name: String,
        controllerClass: {
            type: String,
            default: 'active',
        },
        controlsClass: {
            type: String,
            default: 'active',
        },
        inverseMode: {
            type: Boolean,
            default: false,
        },
    };

    connect() {
        this.element.setAttribute('id', this.nameValue);
        this.blurTimeout = null;
    }

    buttonTargetConnected(button) {
        button.setAttribute('aria-controls', this.nameValue);
        button.setAttribute('aria-expanded', 'false');

        if (this.inverseModeValue) {
            button.setAttribute('tabIndex', -1);
        }
    }

    dropdownTargetConnected(dropdown) {
        if (!this.inverseModeValue) {
            return;
        }

        dropdown.classList.add('invisible');

        const focusable = this.dropdownTarget.querySelectorAll('a[href], button');

        for (const element of focusable) {
            element.dataset.action = 'blur->contao--toggle-state#event focus->contao--toggle-state#event';
        }
    }

    toggleState(state) {
        this.buttonTarget.classList.toggle(this.controllerClassValue, state);
        this.buttonTarget.setAttribute('aria-expanded', state);
        this.dropdownTarget.classList.toggle(this.controlsClassValue, this.inverseModeValue ? !state : state);
    }

    toggle() {
        const isOpen = this.buttonTarget.ariaExpanded === 'true';

        this.toggleState(!isOpen);
    }

    open() {
        this.toggleState(true);
    }

    close() {
        this.toggleState(false);
    }

    event(e) {
        if (e.type === 'blur') {
            this.blurTimeout = setTimeout(() => this.toggleState(false), 100);
            return;
        }

        this.toggleState(true);

        clearTimeout(this.blurTimeout);
    }

    documentClick(event) {
        if (this.buttonTarget.contains(event.target)) {
            return;
        }

        this.toggleState(false);
    }
}
