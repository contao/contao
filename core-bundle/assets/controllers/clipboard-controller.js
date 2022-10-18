/*!
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static values = {
        content: String
    }

    write () {
        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(this.contentValue).catch(this.clipboardFallback.bind(this));
        } else {
            this.clipboardFallback();
        }
    }

    clipboardFallback  () {
        const input = document.createElement('input');
        input.value = this.contentValue;
        document.body.appendChild(input);
        input.select();
        input.setSelectionRange(0, 99999);
        document.execCommand('copy');
        document.body.removeChild(input);
    }
}
