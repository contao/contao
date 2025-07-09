import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['message', 'messagePrototype'];

    messageTargetConnected(el) {
        if (el.hasAttribute(`data-${this.identifier}-autoclose`)) {
            setTimeout(() => this._hide(el), el.getAttribute(`data-${this.identifier}-autoclose`));
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
            console.error(`Could not find any message prototypes for type "${type}".`);
        }
    }

    _hide(el) {
        el.hidden = true;
    }
}
