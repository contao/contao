import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    #closeDelay = null;

    static targets = ['controller', 'controls'];
    static classes = ['active', 'inactive'];
    static values = {
        activeLabel: String,
        inactiveLabel: String,
        activeTitle: String,
        inactiveTitle: String,
    };

    connect() {
        if (!this.controlsTarget.id) {
            this.controlsTarget.setAttribute('id', (Math.random() + 1).toString(36).substring(7));
        }

        this.controllerTargets.forEach((controllerTarget) => {
            controllerTarget.setAttribute('aria-controls', this.controlsTarget.id);
            controllerTarget.setAttribute('aria-expanded', 'false');
        });
    }

    toggle() {
        const isOpen = 'true' === this.controllerTarget.ariaExpanded;

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
        if (
            this.controllerTargets.filter(t => t.contains(event.target)).length > 0
            || this.controlsTarget.contains(event.target)
        ) {
            return;
        }

        this.#toggleState(false);
    }

    #toggleState(state) {
        clearTimeout(this.#closeDelay);

        if (this.hasActiveClass) {
            this.controlsTarget.classList.toggle(this.activeClass, state);
        }

        if (this.hasInactiveClass) {
            this.controlsTarget.classList.toggle(this.inactiveClass, !state);
        }

        this.controllerTargets.forEach((controllerTarget) => {
            controllerTarget.classList.toggle('active', state);
            controllerTarget.setAttribute('aria-expanded', state);

            if (state && this.hasActiveLabelValue) {
                controllerTarget.innerText = this.activeLabelValue;
            } else if (!state && this.hasInactiveLabelValue) {
                controllerTarget.innerText = this.inactiveLabelValue;
            }

            if (state && this.hasActiveTitleValue) {
                controllerTarget.title = this.activeTitleValue;
            } else if (!state && this.hasInactiveTitleValue) {
                controllerTarget.title = this.inactiveTitleValue;
            }
        });
    }
}
