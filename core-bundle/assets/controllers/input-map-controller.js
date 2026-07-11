import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['input', 'source'];

    static values = {
        attribute: String,
    };

    update() {
        const value = [];

        for (const el of this.sourceTargets) {
            value.push(el.getAttribute(this.attributeValue));
        }

        this.inputTarget.value = value.join(',');
    }

    removeElement(event) {
        const el = this.#getElement(event);

        if (!el) {
            return;
        }

        el.remove();

        this.update();
    }

    #getElement(event) {
        if (event.params.closest) {
            return event.target.closest(event.params.closest);
        }

        if (event.params.querySelector) {
            return event.target.querySelector(event.params.querySelector);
        }

        return event.target;
    }
}
