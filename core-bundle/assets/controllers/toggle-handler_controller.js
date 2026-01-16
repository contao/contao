import { Controller } from '@hotwired/stimulus'

export default class extends Controller {
    static outlets = ['contao--toggle-receiver'];

    contaoToggleReceiverOutletConnected(receiver) {
        const controls = (this.element.getAttribute('aria-controls') ?? '').split(' ').filter(Boolean);

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
}
