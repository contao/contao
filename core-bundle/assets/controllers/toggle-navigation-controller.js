import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static classes = ['collapsed']

    static values = {
        url: String,
        requestToken: String,
        expandTitle: String,
        collapseTitle: String,
    }

    toggle ({ currentTarget, params: { category }}) {
        const el = currentTarget.parentNode;
        const collapsed = el.classList.toggle(this.collapsedClass);

        currentTarget.setAttribute('aria-expanded', collapsed ? 'false' : 'true');
        currentTarget.setAttribute('title', collapsed ? this.expandTitleValue : this.collapseTitleValue);

        this.sendRequest(category, collapsed)
    }

    sendRequest (category, collapsed) {
        fetch(this.urlValue, {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: new URLSearchParams({
                action: 'toggleNavigation',
                id: category,
                state: collapsed ? 1 : 0,
                REQUEST_TOKEN: this.requestTokenValue
            })
        });
    }
}
