import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static outlets = ['contao--toggle-receiver'];
    static values = {
        activeLabel: String,
        inactiveLabel: String,
        activeTitle: String,
        inactiveTitle: String,
    };

    contaoToggleReceiverOutletConnected(receiver) {
        const controls = (this.element.getAttribute('aria-controls') ?? '').split(' ').filter(Boolean);

        if (!receiver.element.id) {
            receiver.element.setAttribute('id', (Math.random() + 1).toString(36).substring(7));
        }

        if (!controls.includes(receiver.element.id)) {
            controls.push(receiver.element.id);
        }

        this.element.setAttribute('aria-controls', controls.join(' '));
        this.element.setAttribute('aria-expanded', receiver.isOpen());
    }

    toggle(event) {
        for (const receiver of this.contaoToggleReceiverOutlets) {
            receiver.toggle(event);
        }
    }

    open(event) {
        for (const receiver of this.contaoToggleReceiverOutlets) {
            receiver.open(event);
        }
    }

    close(event) {
        for (const receiver of this.contaoToggleReceiverOutlets) {
            receiver.close(event);
        }
    }

    /**
     * Updates the attributes and label of the handler according to the given state, whenever applicable.
     * Executed by the receiver outlet whenever its state changes by handlers or its own actions.
     */
    setState(state) {
        this.element.classList.toggle('active', state);
        this.element.setAttribute('aria-expanded', state);

        if (state) {
            if (this.activeTitleValue) {
                this.element.title = this.activeTitleValue;
            }

            if (this.hasLabelTarget && this.activeLabelValue) {
                this.labelTarget.textContent = this.activeLabelValue;
            }
        } else {
            if (this.inactiveTitleValue) {
                this.element.title = this.inactiveTitleValue;
            }

            if (this.hasLabelTarget && this.inactiveLabelValue) {
                this.labelTarget.textContent = this.inactiveLabelValue;
            }
        }
    }
}
