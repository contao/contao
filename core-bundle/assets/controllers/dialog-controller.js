import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    connect() {
        if (this.element.open) {
            return;
        }

        this.element.showModal();
        this.focus();
    }

    focus() {
        // Focus the first input element if present
        this.element.querySelector('input')?.focus();

        // Select the text of the first text input element if present
        this.element.querySelector('input[type="text"]')?.select();
    }

    close() {
        this.element.remove();
    }

    suspend() {
        if (this.element.open) {
            this.element.close();
        }
    }

    resume() {
        if (!this.element.open) {
            this.element.showModal();
        }
    }
}
