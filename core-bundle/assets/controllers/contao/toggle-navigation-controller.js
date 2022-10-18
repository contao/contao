import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['link']
    static classes = ['collapsed']

    static values = {
        url: String,
        category: String,
        expandTitle: String,
        collapseTitle: String,
    }

    toggle () {
        if (this.isCollapsed()) {
            this.element.classList.remove(this.collapsedClass);
            this.linkTarget.setAttribute('aria-expanded', 'true');
            this.linkTarget.setAttribute('title', this.collapseTitleValue);
            this.sendRequest(true)
        } else {
            this.element.classList.add(this.collapsedClass);
            this.linkTarget.setAttribute('aria-expanded', 'false');
            this.linkTarget.setAttribute('title', this.expandTitleValue);
            this.sendRequest(false)
        }
    }

    isCollapsed () {
        return this.element.classList.contains(this.collapsedClass);
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
