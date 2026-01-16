import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['label'];
    static classes = ['active', 'inactive'];
    static outlets = ['contao--toggle-handler'];

    static values = {
        activeLabel: String,
        inactiveLabel: String,
        activeTitle: String,
        inactiveTitle: String,
    };

    #closeDelay = null;

    connect() {
        if (!this.element.id) {
            this.element.setAttribute('id', (Math.random() + 1).toString(36).substring(7));
        }

        if (!this.element.hasAttribute('tabindex')) {
            this.element.setAttribute('tabindex', '-1');
        }
    }

    toggle(event) {
        this.#toggleState(!this.isOpen(), event);
    }

    open(event) {
        if (this.isOpen()) {
            return;
        }

        this.#toggleState(true, event);
    }

    close(event) {
        if (!this.isOpen()) {
            return;
        }

        if (event?.params.closeDelay) {
            clearTimeout(this.#closeDelay);
            this.#closeDelay = setTimeout(() => this.#toggleState(false), event.params.closeDelay);
            return;
        }

        this.#toggleState(false, event);
    }

    documentClick(event) {
        if (
            !this.isOpen() ||
            this.contaoToggleHandlerOutlets.filter((t) => t.element.contains(event.target)).length > 0 ||
            this.element.contains(event.target)
        ) {
            return;
        }

        this.#toggleState(false);
    }

    #toggleState(state, event = null) {
        clearTimeout(this.#closeDelay);

        if (this.hasActiveClass) {
            this.element.classList.toggle(this.activeClass, state);
        }

        if (this.hasInactiveClass) {
            this.element.classList.toggle(this.inactiveClass, !state);
        }

        for (const handler of this.contaoToggleHandlerOutlets.map((handler) => handler.element)) {
            handler.classList.toggle('active', state);
            handler.setAttribute('aria-expanded', state);

            if (state && this.hasActiveTitleValue) {
                handler.title = this.activeTitleValue;
            } else if (!state && this.hasInactiveTitleValue) {
                handler.title = this.inactiveTitleValue;
            }
        }

        for (const el of this.hasLabelTarget
            ? this.labelTargets
            : this.contaoToggleHandlerOutlets.map((handler) => handler.element)) {
            if (state && this.hasActiveLabelValue) {
                el.innerText = this.activeLabelValue;
            } else if (!state && this.hasInactiveLabelValue) {
                el.innerText = this.inactiveLabelValue;
            }
        }

        if (['mouseenter', 'mouseover', 'mouseleave'].includes(event?.type)) {
            return;
        }

        setTimeout(() => {
            if (state) {
                this.element.focus();
            } else {
                this.contaoToggleHandlerOutlet.element.focus();
            }
        }, 50);
    }

    isOpen() {
        return 'true' === this.contaoToggleHandlerOutlet.element.ariaExpanded;
    }
}
