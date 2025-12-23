import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    #filterMap = new Map();

    static targets = ['count', 'panel', 'filter'];

    connect() {
        if (this.hasCountTarget) {
            this.#updateCountNumber();
        }
    }

    disconnect() {
        if (this.hasCountTarget) {
            this.countTarget.innerText = '';
        }

        this.#filterMap.clear();
    }

    filterTargetConnected(filter) {
        let value = filter.value ?? null;

        if (!value && filter.classList.contains('tl_select_wrapper')) {
            const name = `tl_${filter.firstElementChild.name}`;
            value = filter.firstElementChild?.value;

            // The select filters use their name as their default value
            if (name === value) {
                value = null;
            }
        }

        this.#filterMap.set(filter, value);
    }

    filterTargetDisconnected(filter) {
        if (!this.#filterMap.has(filter)) {
            return;
        }

        this.#filterMap.get(filter).remove();
        this.#filterMap.delete(filter);
    }

    updateCount(el) {
        let target = el.currentTarget;
        const value = target.value;

        if (!this.#filterMap.has(target)) {
            // Could be wrapped by choices
            const choicesTarget = el.currentTarget.closest('.choices');

            if (!choicesTarget) {
                return;
            }

            target = choicesTarget;
        }

        this.#filterMap.set(target, value);

        this.#updateCountNumber();
    }

    #updateCountNumber() {
        let count = 0;

        for (const value of this.#filterMap.values()) {
            if (value) {
                count++;
            }
        }

        this.countTarget.innerText = count !== 0 ? count : '';
    }
}
