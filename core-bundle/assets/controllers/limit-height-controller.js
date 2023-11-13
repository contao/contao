import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static values = {
        max: {
            type: Number,
            default: 112,
        },
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
    }

    /**
     * Automatically register target on all legacy ".limit_height" classes
     */
    connect () {
        const registerLegacy = () => {
            document.querySelectorAll('div.limit_height').forEach((div) => {
                const parent = div.parentNode.closest('.tl_content');

                // Return if the node is a wrapper
                if (parent && (parent.classList.contains('wrapper_start') || parent.classList.contains('wrapper_stop'))) return;

                const hgt = Number(div.className.replace(/[^0-9]*/, ''))

                // Return if there is no height value
                if (!hgt) return;

                // Use the last found max height as the controller value
                this.maxValue = hgt;

                div.setAttribute(this.application.schema.targetAttributeForScope(this.identifier), 'node');
            });
        };

        // called as soon as registered so DOM may not have loaded yet
        if (document.readyState === "loading") {
            document.addEventListener("DOMContentLoaded", registerLegacy)
        } else {
            registerLegacy()
        }
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

        node.style.position = 'relative';
        node.style.overflow = 'hidden';
        node.style.maxHeight = `${this.maxValue}px`;

        const button = document.createElement('button');
        button.setAttribute('type', 'button');
        button.title = this.expandValue;
        button.innerHTML = '<span>...</span>';
        button.classList.add('unselectable');

        button.addEventListener('click', (event) => {
            event.preventDefault();
            this.toggle(node);
            this.updateOperation(event);
        });

        const toggler = document.createElement('div');
        toggler.classList.add('limit_toggler');
        toggler.append(button);

        this.togglerMap.set(node, toggler);

        node.append(toggler);
    }

    nodeTargetDisconnected (node) {
        if (!this.togglerMap.has(node)) {
            return;
        }

        this.togglerMap.get(node).remove();
        this.togglerMap.delete(node);
        node.style.position = '';
        node.style.overflow = '';
        node.style.maxHeight = '';
    }

    toggle (node) {
        if (node.style.maxHeight === '') {
            this.collapse(node);
        } else {
            this.expand(node);
        }

        console.log(node.style.maxHeight);
    }

    expand (node) {
        node.style.maxHeight = '';
        this.setButtonTitle(node, this.collapseValue);
    }

    collapse (node) {
        node.style.maxHeight = `${this.maxValue}px`;
        this.setButtonTitle(node, this.expandValue);
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
        this.operationTarget.style.display = hasTogglers ? '' : 'none';

        if (this.hasExpanded() ^ (event ? event.altKey : false)) {
            this.operationTarget.innerText = this.collapseAllValue;
            this.operationTarget.title = this.collapseAllTitleValue;
        } else {
            this.operationTarget.innerText = this.expandAllValue;
            this.operationTarget.title = this.expandAllTitleValue;
        }
    }

    maxValueChanged () {
        this.nodeTargets.forEach((node) => {
            this.nodeTargetDisconnected(node);
            this.nodeTargetConnected(node);
        })
    }

    hasExpanded () {
        return !!this.nodeTargets.find((el) => this.togglerMap.has(el) && el.style.maxHeight === '');
    }

    setButtonTitle (node, title) {
        if (!this.togglerMap.has(node)) {
            return;
        }

        this.togglerMap.get(node).querySelector('button').title = title;
    }
}
