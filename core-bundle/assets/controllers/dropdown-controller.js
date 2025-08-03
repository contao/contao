import { Controller } from '@hotwired/stimulus';

export default class DropdownController extends Controller {
    static targets = ['button', 'dropdown'];

    static values = {
        name: String,
        activeClass: {
            type: String,
            default: 'active',
        },
        buttonActiveClass: {
            type: String,
            default: 'active',
        },
        invisibleClass: {
            type: String,
            default: 'invisible',
        },
        invisibleMode: {
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

        if (this.invisibleModeValue) {
            button.setAttribute('tabIndex', -1);
        }
    }

    dropdownTargetConnected(dropdown) {
        if (!this.invisibleModeValue) {
            return;
        }

        dropdown.classList.add('invisible');

        const focusable = this.dropdownTarget.querySelectorAll('a[href], button');

        for (const element of focusable) {
            element.dataset.action = 'blur->contao--dropdown#event focus->contao--dropdown#event';
        }
    }

    toggleState(state) {
        this.buttonTarget.classList.toggle(this.buttonActiveClassValue, state);
        this.buttonTarget.setAttribute('aria-expanded', state);

        if (this.invisibleModeValue) {
            this.dropdownTarget.classList.toggle(this.invisibleClassValue, !state);
        } else {
            this.dropdownTarget.classList.toggle(this.activeClassValue, state);
        }
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
