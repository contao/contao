import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['label'];
    static classes = ['active', 'inactive'];
    static outlets = ['contao--toggle-handler'];

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

        for (const handler of this.contaoToggleHandlerOutlets) {
            handler.setState(state);
        }

        if (['mouseenter', 'mouseover', 'mouseleave'].includes(event?.type)) {
            return;
        }

        setTimeout(() => {
            if (state) {
                this.element.focus();
            } else {
                if (this.hasContaoToggleHandlerOutlet) {
                    this.contaoToggleHandlerOutlet.element.focus();
                }
            }
        }, 50);
    }

    isOpen() {
        if (this.hasContaoToggleHandlerOutlet) {
            return 'true' === this.contaoToggleHandlerOutlet.element.ariaExpanded;
        }

        if (this.hasActiveClass) {
            return this.element.classList.contains(this.activeClass);
        }

        if (this.hasInactiveClass) {
            return !this.element.classList.contains(this.inactiveClass);
        }

        return false;
    }
}
