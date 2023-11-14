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
        return new Promise((resolve) => {
            new Request.Contao({
                field: el,
                evalScripts: true,
                onRequest: () => {
                    this.loadToggler(el, true);
                },
                onSuccess: (txt) => {
                    const target = level === 0 ? 'child rootChild' : 'child';
                    var li = new Element('li', {
                        'id': id,
                        'class': 'parent',
                        'styles': {
                            'display': 'inline'
                        },
                        [`data-${this.identifier}-target`]: target
                    });

                    new Element('ul', {
                        'class': 'level_' + level,
                        'html': txt
                    }).inject(li, 'bottom');

                    if (this.modeValue === 5) {
                        li.inject($(el).getParent('li'), 'after');
                    } else {
                        var isFolder = false,
                            parent = $(el).getParent('li'),
                            next;

                        while (typeOf(parent) === 'element' && (next = parent.getNext('li'))) {
                            parent = next;
                            if (parent.hasClass('tl_folder')) {
                                isFolder = true;
                                break;
                            }
                        }

                        if (isFolder) {
                            li.inject(parent, 'before');
                        } else {
                            li.inject(parent, 'after');
                        }
                    }

                    // Update the referer ID
                    li.getElements('a').each(function(el) {
                        el.href = el.href.replace(/&ref=[a-f0-9]+/, '&ref=' + Contao.referer_id);
                    });

                    window.fireEvent('structure');
                    this.loadToggler(el, false);
                    this.expandToggler(el);

                    // HOOK
                    window.fireEvent('ajax_change');

                    resolve();
                }
            }).post({
                'action': this.loadActionValue,
                'id': id,
                'level': level,
                'folder': folder,
                'state': 1,
                'REQUEST_TOKEN': this.requestTokenValue
            });
        });
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
        return new Promise((resolve) => {
            new Request.Contao({
                field: el,
                onComplete: resolve
            }).post({
                'action': this.toggleActionValue,
                'id': id,
                'state': state,
                'REQUEST_TOKEN': this.requestTokenValue
            });
        });
    }

    async updateAllState (href, state) {
        return new Promise((resolve) => {
            new Request.Contao({
                url:`${href}&state=${state}`,
                followRedirects: false,
                onComplete: resolve
            }).get();
        });
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
