import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['button', 'dropdown'];

    static values = {
        name: String,
        buttonActiveClass: {
            type: String,
            default: 'active',
        },
        dropdownActiveClass: {
            type: String,
            default: 'active',
        },
    };

    connect() {
        this.element.setAttribute('id', this.nameValue);
    }

    buttonTargetConnected(button) {
        button.setAttribute('aria-controls', this.nameValue);
        button.setAttribute('aria-expanded', 'false');
    }

    toggle(event) {
        this.dropdownTarget.classList.toggle(this.dropdownActiveClassValue);
        this.buttonTarget.classList.toggle(this.buttonActiveClassValue);
        this.buttonTarget.setAttribute('aria-expanded', this.element.classList.contains(this.dropdownActiveClassValue));
    }

    close() {
        this.dropdownTarget.classList.remove(this.dropdownActiveClassValue);
        this.buttonTarget.classList.remove(this.buttonActiveClassValue);
        this.buttonTarget.setAttribute('aria-expanded', 'false');
    }

    documentClick(event) {
        if (this.buttonTarget.contains(event.target)) {
            return;
        }

        this.close();
    }
}
