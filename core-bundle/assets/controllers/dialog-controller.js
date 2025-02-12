import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    connect() {
        if(this.element.open) {
            return;
        }

        this.element.showModal();
        this.focus();
    }

    focus() {
        // Focus first input element if existing
        this.element.querySelector('input')?.focus();

        // Select text of first text input element if existing
        this.element.querySelector('input[type="text"]')?.select();
    }

    close() {
        // Currently, we never want to reopen a dialog, so we are removing the
        // element instead of calling close().
        this.element.remove();
    }
}
