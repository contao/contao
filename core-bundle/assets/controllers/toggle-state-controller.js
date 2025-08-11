import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['controller', 'controls'];
    static classes = ['active', 'inactive'];

    #closeDelay = null;

    connect() {
        if (!this.controlsTarget.id) {
            this.controlsTarget.setAttribute('id', (Math.random() + 1).toString(36).substring(7));
        }

        this.controllerTarget.setAttribute('aria-controls', this.controlsTarget.id);
        this.controllerTarget.setAttribute('aria-expanded', 'false');
    }

    toggle() {
        const isOpen = this.controllerTarget.ariaExpanded === 'true';

        this.#toggleState(!isOpen);
    }

    open() {
        this.#toggleState(true);
    }

    close(event) {
        if (event?.params.closeDelay) {
            clearTimeout(this.#closeDelay);
            this.#closeDelay = setTimeout(() => this.#toggleState(false), event.params.closeDelay);
            return;
        }

        this.#toggleState(false);
    }

    documentClick(event) {
        if (this.controllerTarget.contains(event.target) || this.controlsTarget.contains(event.target)) {
            return;
        }

        this.#toggleState(false);
    }

    #toggleState(state) {
        clearTimeout(this.#closeDelay);

        this.controllerTarget.classList.toggle('active', state);
        this.controllerTarget.setAttribute('aria-expanded', state);

        if (this.hasActiveClass) {
            this.controlsTarget.classList.toggle(this.activeClass, state);
        }

        if (this.hasInactiveClass) {
            this.controlsTarget.classList.toggle(this.inactiveClass, !state);
        }
    }
}
