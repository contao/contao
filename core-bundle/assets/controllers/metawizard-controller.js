/*!
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['input']

    delete () {
        this.inputTargets.forEach((input) => {
            input.value = '';
        })
    }
}
