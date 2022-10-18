/*!
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    write (event) {
        event.preventDefault();

        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(this.element.href).catch(this.clipboardFallback.bind(this));
        } else {
            this.clipboardFallback();
        }
    }

    clipboardFallback  () {
        const input = document.createElement('input');
        input.value = this.element.href;
        document.body.appendChild(input);
        input.select();
        input.setSelectionRange(0, 99999);
        document.execCommand('copy');
        document.body.removeChild(input);
    }
}
