import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['controller', 'controls'];

    static values = {
        controllerClass: {
            type: String,
            default: 'active',
        },
        controlsClass: {
            type: String,
            default: 'active',
        },
        inverseMode: {
            type: Boolean,
            default: false,
        },
    };

    connect() {
        this.element.setAttribute('id', this.id);
        this.blurTimeout = null;
    }

    controllerTargetConnected(controller) {
        // The target connect lifecycle callback fires before connect() so we apply the id here
        this.id = !!this.element.id ? this.element.id : (Math.random() + 1).toString(36).substring(7);

        controller.setAttribute('aria-controls', this.id);
        controller.setAttribute('aria-expanded', 'false');

        if (this.inverseModeValue) {
            controller.setAttribute('tabIndex', -1);
        }
    }

    controlsTargetConnected(controls) {
        if (!this.inverseModeValue) {
            return;
        }

        controls.classList.add('invisible');

        const focusable = this.controlsTarget.querySelectorAll('a[href], button');

        for (const element of focusable) {
            element.dataset.action = 'blur->contao--toggle-state#event focus->contao--toggle-state#event';
        }
    }

    toggleState(state) {
        this.controllerTarget.classList.toggle(this.controllerClassValue, state);
        this.controllerTarget.setAttribute('aria-expanded', state);
        this.controlsTarget.classList.toggle(this.controlsClassValue, this.inverseModeValue ? !state : state);
    }

    toggle() {
        const isOpen = this.controllerTarget.ariaExpanded === 'true';

        this.toggleState(!isOpen);
    }

    open() {
        this.toggleState(true);
    }

    close() {
        this.toggleState(false);
    }

    event(e) {
        if (e.type === 'blur') {
            this.blurTimeout = setTimeout(() => this.toggleState(false), 100);
            return;
        }

        this.toggleState(true);

        clearTimeout(this.blurTimeout);
    }

    documentClick(event) {
        if (this.controllerTarget.contains(event.target) || this.controlsTarget.contains(event.target)) {
            return;
        }

        this.toggleState(false);
    }
}
