/*!
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['link']

    static values = {
        url: String,
        category: String
    }

    toggle (event) {
        event.preventDefault();

        if (this.isCollapsed()) {
            this.element.classList.remove('collapsed');
            this.linkTarget.setAttribute('aria-expanded', 'true');
            this.linkTarget.setAttribute('title', Contao.lang.collapse);
            this.sendRequest(true)
        } else {
            this.element.classList.add('collapsed');
            this.linkTarget.setAttribute('aria-expanded', 'false');
            this.linkTarget.setAttribute('title', Contao.lang.expand);
            this.sendRequest(false)
        }
    }

    isCollapsed () {
        return this.element.classList.contains('collapsed');
    }

    sendRequest (collapsed) {
        fetch(this.urlValue, {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: new URLSearchParams({
                action: 'toggleNavigation',
                id: this.categoryValue,
                state: collapsed ? 1 : 0,
                REQUEST_TOKEN: Contao.request_token
            })
        });
    }
}
