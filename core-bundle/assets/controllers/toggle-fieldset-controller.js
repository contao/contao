import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static values = {
        id: String,
        table: String,
    };

    static classes = ['collapsed'];

    connect() {
        if (this.element.querySelectorAll('label.error, label.mandatory').length) {
            this.element.classList.remove(this.collapsedClass);
        }

        if (this.element.classList.contains(this.collapsedClass)) {
            this.setAriaExpanded(false);
        } else {
            this.setAriaExpanded(true);
        }
    }

    toggle() {
        if (this.element.classList.contains(this.collapsedClass)) {
            this.open();
            this.setAriaExpanded(true);
        } else {
            this.close();
            this.setAriaExpanded(false);
        }
    }

    open() {
        if (!this.element.classList.contains(this.collapsedClass)) {
            return;
        }

        this.element.classList.remove(this.collapsedClass);
        this.storeState(1);
    }

    close() {
        if (this.element.classList.contains(this.collapsedClass)) {
            return;
        }

        const form = this.element.closest('form');
        const input = this.element.querySelectorAll('[required]');

        let collapse = true;
        for (let i = 0; i < input.length; i++) {
            if (!input[i].value) {
                collapse = false;
                break;
            }
        }

        if (!collapse) {
            if (typeof form.checkValidity === 'function') {
                form.querySelector('button[type="submit"]').click();
            }
        } else {
            this.element.classList.add(this.collapsedClass);
            this.storeState(0);
        }
    }

    storeState(state) {
        if (!this.hasIdValue || !this.hasTableValue) {
            return;
        }

        fetch(window.location.href, {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: new URLSearchParams({
                action: 'toggleFieldset',
                id: this.idValue,
                table: this.tableValue,
                state: state,
            }),
        });
    }

    setAriaExpanded(state) {
        const button = this.element.querySelector('button');

        if (button) {
            button.ariaExpanded = state;
        }
    }
}
