import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['message'];

    messageTargetConnected(el) {
        if(!el.querySelector('button.close')) {
            setTimeout(() => this._hide(el), 5000);
        }
    }

    close(event) {
        this._hide(event.target.closest('*[data-contao--message-outlet-target]'));
    }

    _hide(el) {
        el.hidden = true;
    }
}
