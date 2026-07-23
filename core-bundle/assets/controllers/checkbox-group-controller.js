import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['toggle'];

    toggleTargetConnected(toggle) {
        this.#syncToggle(toggle);
    }

    toggleGroup(event) {
        const toggle = event.currentTarget;

        if (!(toggle instanceof HTMLInputElement) || 'checkbox' !== toggle.type) {
            return;
        }

        const changedInputs = this.#getControlledInputs(toggle).filter((input) => input.checked !== toggle.checked);

        for (const input of changedInputs) {
            input.checked = toggle.checked;
        }

        for (const input of changedInputs) {
            input.dispatchEvent(new Event('change', { bubbles: true }));
        }

        this.#syncToggle(toggle);
    }

    sync() {
        for (const toggle of this.toggleTargets) {
            this.#syncToggle(toggle);
        }
    }

    #syncToggle(toggle) {
        if (!(toggle instanceof HTMLInputElement) || 'checkbox' !== toggle.type) {
            return;
        }

        const inputs = this.#getControlledInputs(toggle);
        const checked = inputs.filter((input) => input.checked).length;

        toggle.checked = inputs.length > 0 && checked === inputs.length;
        toggle.indeterminate = checked > 0 && checked < inputs.length;
    }

    #getControlledInputs(toggle) {
        return (toggle.getAttribute('aria-controls') ?? '')
            .trim()
            .split(/\s+/)
            .filter(Boolean)
            .map((id) => document.getElementById(id))
            .filter((input) => input instanceof HTMLInputElement && 'checkbox' === input.type);
    }
}
