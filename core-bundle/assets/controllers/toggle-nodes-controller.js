import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static values = {
        mode: {
            type: Number,
            default: 5,
        },
        toggleAction: String,
        loadAction: String,
        requestToken: String,
        expand: String,
        collapse: String,
        expandAll: String,
        expandAllTitle: String,
        collapseAll: String,
        collapseAllTitle: String,
    };

    static targets = ['operation', 'node', 'toggle', 'child', 'rootChild'];

    operationTargetConnected() {
        this.updateOperation();
    }

    childTargetConnected() {
        this.updateOperation();
    }

    toggle(event) {
        const el = event.currentTarget;
        this.toggleToggler(el, event.params.id, event.params.level, event.params.folder);
    }

    toggleToggler(el, id, level, folder) {
        const item = document.getElementById(id);

        if (!item?.childElementCount) {
            // Empty lists have not been loaded yet
            this.fetchChild(el, id, level, folder);
        } else if (item.style.display === 'none') {
            this.showChild(item);
            this.expandToggler(el);
            this.updateState(el, id, 1);
        } else {
            this.hideChild(item);
            this.collapseToggler(el);
            this.updateState(el, id, 0);
        }

        this.updateOperation();
    }

    expandToggler(el) {
        el.classList.add('foldable--open');

        if (el.hasAttribute('title')) {
            el.title = this.collapseValue;
        }

        for (const image of el.querySelectorAll('img')) {
            image.alt = this.collapseValue;
        }
    }

    collapseToggler(el) {
        el.classList.remove('foldable--open');

        if (el.hasAttribute('title')) {
            el.title = this.expandValue;
        }

        for (const image of el.querySelectorAll('img')) {
            image.alt = this.expandValue;
        }
    }

    loadToggler(el, enabled) {
        el.classList[enabled ? 'add' : 'remove']('foldable--loading');
    }

    showChild(item) {
        item.style.display = '';
    }

    hideChild(item) {
        item.style.display = 'none';
    }

    async fetchChild(el, id, level, folder) {
        const list = document.getElementById(id);

        if (!list) {
            return;
        }

        this.loadToggler(el, true);

        const response = await fetch(new URL(location.href), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: new URLSearchParams({
                action: this.loadActionValue,
                id: id,
                level: level,
                folder: folder,
                state: 1,
                REQUEST_TOKEN: this.requestTokenValue,
            }),
        });

        if (response.ok) {
            list.innerHTML = await response.text();
            this.showChild(list);

            window.dispatchEvent(new CustomEvent('structure'));
            this.expandToggler(el);

            // HOOK (see #6752)
            window.fireEvent('ajax_change');
        }

        this.loadToggler(el, false);
    }

    async toggleAll(event) {
        const href = event.currentTarget.href;

        if (this.hasExpandedRoot() ^ (event ? event.altKey : false)) {
            this.updateAllState(href, 0);

            for (const el of this.toggleTargets) {
                this.collapseToggler(el);
            }

            for (const item of this.childTargets) {
                item.style.display = 'none';
            }
        } else {
            for (const el of this.childTargets) {
                el.innerHTML = '';
            }

            for (const el of this.toggleTargets) {
                this.loadToggler(el, true);
            }

            await this.updateAllState(href, 1);
            const promises = [];

            for (const el of this.toggleTargets) {
                promises.push(
                    this.fetchChild(
                        el,
                        el.getAttribute(`data-${this.identifier}-id-param`),
                        0,
                        el.getAttribute(`data-${this.identifier}-folder-param`),
                    ),
                );
            }

            await Promise.all(promises);
        }

        this.updateOperation();
    }

    keypress(event) {
        this.updateOperation(event);
    }

    async updateState(_el, id, state) {
        await fetch(location.href, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: new URLSearchParams({
                action: this.toggleActionValue,
                id: id,
                state: state,
                REQUEST_TOKEN: this.requestTokenValue,
            }),
        });
    }

    async updateAllState(href, state) {
        await fetch(`${href}&state=${state}`);
    }

    updateOperation(event) {
        if (!this.hasOperationTarget) {
            return;
        }

        for (const operationTarget of this.operationTargets) {
            if (this.hasExpandedRoot() ^ (event ? event.altKey : false)) {
                operationTarget.innerText = this.collapseAllValue;
                operationTarget.title = this.collapseAllTitleValue;
            } else {
                operationTarget.innerText = this.expandAllValue;
                operationTarget.title = this.expandAllTitleValue;
            }
        }
    }

    hasExpandedRoot() {
        return !!this.rootChildTargets.find((el) => el.style.display !== 'none');
    }
}
