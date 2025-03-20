import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['message', 'messagePrototype'];

    messageTargetConnected(el) {
        if (!el.querySelector('button.close')) {
            setTimeout(() => this._hide(el), 5000);
        }
    }

    close(event) {
        this._hide(event.target.closest('*[data-contao--message-outlet-target]'));
    }

    renderMessage(event) {
        const { type, message } = event.detail;

        for (const target of this.messagePrototypeTargets) {
            if (target.dataset.type === type) {
                const html = target.getHTML().replace('{{message}}', message);
                this.element.append(document.createRange().createContextualFragment(html));

                return;
            }
        }

        if (window.console) {
            console.error(`Did not find any message prototypes for type "${type}".`);
        }
    }

    _hide(el) {
        el.hidden = true;
    }
}
