import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static values = {
        mode: {
            type: Number,
            default: 5
        },
        toggleAction: String,
        loadAction: String,
        requestToken: String,
        refererId: String,
        expand: String,
        collapse: String,
        expandAll: String,
        expandAllTitle: String,
        collapseAll: String,
        collapseAllTitle: String,
    }

    static targets = ['operation', 'node', 'toggle', 'child', 'rootChild'];

    operationTargetConnected () {
        this.updateOperation();
    }

    childTargetConnected () {
        this.updateOperation();
    }

    toggle (event) {
        event.preventDefault();

        const el = event.currentTarget;
        el.blur();

        this.toggleToggler(el, event.params.id, event.params.level, event.params.folder);
    }

    toggleToggler (el, id, level, folder) {
        const item = document.id(id);

        if (item && item.style.display === 'none') {
            this.showChild(item);
            this.expandToggler(el);
            this.updateState(el, id, 1);
        } else if (item) {
            this.hideChild(item);
            this.collapseToggler(el);
            this.updateState(el, id, 0);
        } else {
            this.fetchChild(el, id, level, folder)
        }

        this.updateOperation();
    }

    expandToggler (el) {
        el.classList.add('foldable--open');
        el.title = this.collapseValue;
    }

    collapseToggler (el) {
        el.classList.remove('foldable--open');
        el.title = this.expandValue;
    }

    loadToggler (el, enabled) {
        el.classList[enabled ? 'add' : 'remove']('foldable--loading');
    }

    showChild (item) {
        item.style.display = '';
    }

    hideChild (item) {
        item.style.display = 'none';
    }

    async fetchChild (el, id, level, folder) {
        this.loadToggler(el, true);

        const url = new URL(location.href);
        const search = url.searchParams;
        search.set('ref', this.refererIdValue);
        url.search = search.toString();

        const response = await fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: new URLSearchParams({
                'action': this.loadActionValue,
                'id': id,
                'level': level,
                'folder': folder,
                'state': 1,
                'REQUEST_TOKEN': this.requestTokenValue
            })
        });

        if (response.ok) {
            const txt = await response.text();

            const li = document.createElement('li');
            li.id = id;
            li.classList.add('parent');
            li.style.display = 'inline';
            li.setAttribute(`data-${this.identifier}-target`, level === 0 ? 'child rootChild' : 'child')

            const ul = document.createElement('ul');
            ul.classList.add('level_' + level);
            ul.innerHTML = txt;
            li.append(ul);

            if (this.modeValue === 5) {
                el.closest('li').after(li);
            } else {
                let isFolder = false,
                    parent = el.closest('li'),
                    next;

                while (typeOf(parent) === 'element' && parent.tagName === 'LI' && (next = parent.nextElementSibling)) {
                    parent = next;
                    if (parent.classList.contains('tl_folder')) {
                        isFolder = true;
                        break;
                    }
                }

                if (isFolder) {
                    parent.before(li);
                } else {
                    parent.after(li);
                }
            }

            window.dispatchEvent(new CustomEvent('structure'));
            this.expandToggler(el);

            // HOOK
            window.dispatchEvent(new CustomEvent('ajax_change'));
        }

        this.loadToggler(el, false);
    }

    async toggleAll (event) {
        event.preventDefault();

        const href = event.currentTarget.href;

        if (this.hasExpandedRoot() ^ (event ? event.altKey : false)) {
            this.updateAllState(href, 0);
            this.toggleTargets.forEach((el) => this.collapseToggler(el));
            this.childTargets.forEach((item) => item.style.display = 'none');
        } else {
            this.childTargets.forEach((el) => el.remove());
            this.toggleTargets.forEach((el) => this.loadToggler(el, true));

            await this.updateAllState(href, 1);
            const promises = [];

            this.toggleTargets.forEach((el) => {
                promises.push(this.fetchChild(
                    el,
                    el.getAttribute(`data-${this.identifier}-id-param`),
                    0,
                    el.getAttribute(`data-${this.identifier}-folder-param`)
                ));
            });

            await Promise.all(promises);
        }

        this.updateOperation();
    }

    keypress (event) {
        this.updateOperation(event)
    }

    async updateState (el, id, state) {
        await fetch(location.href, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: new URLSearchParams({
                'action': this.toggleActionValue,
                'id': id,
                'state': state,
                'REQUEST_TOKEN': this.requestTokenValue
            })
        });
    }

    async updateAllState (href, state) {
        await fetch(`${href}&state=${state}`);
    }

    updateOperation (event) {
        if (!this.hasOperationTarget) {
            return;
        }

        if (this.hasExpandedRoot() ^ (event ? event.altKey : false)) {
            this.operationTarget.innerText = this.collapseAllValue;
            this.operationTarget.title = this.collapseAllTitleValue;
        } else {
            this.operationTarget.innerText = this.expandAllValue;
            this.operationTarget.title = this.expandAllTitleValue;
        }
    }

    hasExpandedRoot () {
        return !!this.rootChildTargets.find((el) => el.style.display !== 'none')
    }
}
