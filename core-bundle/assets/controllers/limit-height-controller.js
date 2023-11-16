import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static values = {
        max: Number,
        expand: String,
        collapse: String,
        expandAll: String,
        expandAllTitle: String,
        collapseAll: String,
        collapseAllTitle: String,
    }

    static targets = ['operation', 'node'];

    initialize () {
        super.initialize();
        this.togglerMap = new WeakMap();
        this.nextId = 1;
    }

    operationTargetConnected () {
        this.updateOperation();
    }

    nodeTargetConnected (node) {
        const style = window.getComputedStyle(node, null);
        const padding = parseFloat(style.paddingTop) + parseFloat(style.paddingBottom);
        const height = node.clientHeight - padding;

        // Resize the element if it is higher than the maximum height
        if (this.maxValue > height) {
            return;
        }

        if (!node.id) {
            node.id = `limit-height-${this.nextId++}`;
        }

        node.style.overflow = 'hidden';
        node.style.maxHeight = `${this.maxValue}px`;

        const button = document.createElement('button');
        button.setAttribute('type', 'button');
        button.title = this.expandValue;
        button.innerHTML = '<span>...</span>';
        button.classList.add('unselectable');
        button.setAttribute('aria-expanded', 'false');
        button.setAttribute('aria-controls', node.id);

        button.addEventListener('click', (event) => {
            event.preventDefault();
            this.toggle(node);
            this.updateOperation(event);
        });

        const toggler = document.createElement('div');
        toggler.classList.add('limit_toggler');
        toggler.append(button);

        this.togglerMap.set(node, toggler);

        node.before(toggler);
        this.updateOperation();
    }

    nodeTargetDisconnected (node) {
        if (!this.togglerMap.has(node)) {
            return;
        }

        this.togglerMap.get(node).remove();
        this.togglerMap.delete(node);
        node.style.overflow = '';
        node.style.maxHeight = '';
    }

    toggle (node) {
        if (node.style.maxHeight === '') {
            this.collapse(node);
        } else {
            this.expand(node);
        }
    }

    expand (node) {
        if (!this.togglerMap.has(node)) {
            return;
        }

        node.style.maxHeight = '';
        const button = this.togglerMap.get(node).querySelector('button');
        button.title = this.collapseValue;
        button.setAttribute('aria-expanded', 'true');
    }

    collapse (node) {
        if (!this.togglerMap.has(node)) {
            return;
        }

        node.style.maxHeight = `${this.maxValue}px`;
        const button = this.togglerMap.get(node).querySelector('button');
        button.title = this.expandValue;
        button.setAttribute('aria-expanded', 'false');
    }

    toggleAll (event) {
        event.preventDefault();
        const isExpanded = this.hasExpanded() ^ event.altKey;

        this.nodeTargets.forEach((node) => {
            if (isExpanded) {
                this.collapse(node);
            } else {
                this.expand(node);
            }
        });

        this.updateOperation(event);
    }

    keypress (event) {
        this.updateOperation(event);
    }

    updateOperation (event) {
        if (!this.hasOperationTarget) {
            return;
        }

        const hasTogglers = !!this.nodeTargets.find((el) => this.togglerMap.has(el));
        const expanded = this.hasExpanded();

        this.operationTarget.style.display = hasTogglers ? '' : 'none';
        this.operationTarget.setAttribute('aria-controls', this.nodeTargets.map((el) => el.id).join(' '));
        this.operationTarget.setAttribute('aria-expanded', expanded ? 'true' : 'false');

        if (expanded ^ (event ? event.altKey : false)) {
            this.operationTarget.innerText = this.collapseAllValue;
            this.operationTarget.title = this.collapseAllTitleValue;
        } else {
            this.operationTarget.innerText = this.expandAllValue;
            this.operationTarget.title = this.expandAllTitleValue;
        }
    }

    hasExpanded () {
        return !!this.nodeTargets.find((el) => this.togglerMap.has(el) && el.style.maxHeight === '');
    }
}
