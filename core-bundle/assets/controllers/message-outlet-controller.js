import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['message'];

    messageTargetConnected(el) {
        if (el.hasAttribute(`data-${this.identifier}-autoclose`)) {
            setTimeout(() => this._hide(el), el.getAttribute(`data-${this.identifier}-autoclose`));
        }
    }

    close(event) {
        this._hide(event.target.closest('*[data-contao--message-outlet-target]'));
    }

    _hide(el) {
        el.hidden = true;
    }
}
