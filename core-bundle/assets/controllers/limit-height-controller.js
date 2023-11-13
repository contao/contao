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

    static targets = ['operation', 'element'];

    /**
     * Automatically register target on all legacy ".limit_height" classes
     */
    static afterLoad(identifier, application) {
        const registerLegacy = () => {
            document.querySelectorAll('div.limit_height').forEach(function(div) {
                const parent = div.parentNode.closest('.tl_content');

                // Return if the element is a wrapper
                if (parent && (parent.classList.contains('wrapper_start') || parent.classList.contains('wrapper_stop'))) return;

                const hgt = Number(div.className.replace(/[^0-9]*/, ''))

                // Return if there is no height value
                if (!hgt) return;

                div.setAttribute(application.schema.targetAttributeForScope(identifier), 'element');
            });
        }

        // called as soon as registered so DOM may not have loaded yet
        if (document.readyState === "loading") {
            document.addEventListener("DOMContentLoaded", registerLegacy)
        } else {
            registerLegacy()
        }
    }

    initialize () {
        super.initialize();
        this.togglerMap = new WeakMap();
        this.expanded = 0;
    }

    operationTargetConnected () {
        this.updateOperation();
    }

    elementTargetConnected (element) {
        // Resize the element if it is higher than the maximum height
        element.style.maxHeight = `${this.maxValue}px`;

        const button = document.createElement('button');
        button.setAttribute('type', 'button');
        button.title = this.expandValue;
        button.innerHTML = '<span>...</span>';
        button.classList.add('unselectable');

        button.addEventListener('click', (event) => {
            event.preventDefault();
            this.toggle(element);
            this.updateOperation(event);
        });

        const toggler = document.createElement('div');
        toggler.classList.add('limit_toggler');
        toggler.append(button);

        this.togglerMap.set(element, toggler);

        element.append(toggler);
    }

    elementTargetDisconnected (element) {
        if (!this.togglerMap.has(element)) {
            return;
        }

        this.togglerMap.get(element).remove();
        this.togglerMap.delete(element);
        element.style.maxHeight = 'none';
    }

    toggle (element) {
        if (element.style.maxHeight === 'none') {
            this.collapse(element);
        } else {
            this.expand(element);
        }
    }

    expand (element) {
        element.style.maxHeight = 'none';
        this.setButtonTitle(element, this.collapseValue);
    }

    collapse (element) {
        element.style.maxHeight = `${this.maxValue}px`;
        this.setButtonTitle(element, this.expandValue);
    }

    toggleAll (event) {
        event.preventDefault();
        const isExpanded = this.hasExpanded() ^ event.altKey;

        console.log(isExpanded);

        this.elementTargets.forEach((element) => {
            if (isExpanded) {
                this.collapse(element);
            } else {
                this.expand(element);
            }
        });

        this.updateOperation(event);
    }

    invertAll (event) {
        this.updateOperation(event);
    }

    revertAll (event) {
        this.updateOperation(event);
    }

    updateOperation (event) {
        if (!this.operationTarget) {
            return;
        }

        if (this.hasExpanded() ^ (event ? event.altKey : false)) {
            this.operationTarget.innerText = this.collapseAllValue;
            this.operationTarget.title = this.collapseAllTitleValue;
        } else {
            this.operationTarget.innerText = this.expandAllValue;
            this.operationTarget.title = this.expandAllTitleValue;
        }
    }

    hasExpanded () {
        return !!this.elementTargets.find((el) => el.style.maxHeight === 'none');
    }

    setButtonTitle (element, title) {
        if (!this.togglerMap.has(element)) {
            return;
        }

        this.togglerMap.get(element).querySelector('button').title = this.collapseValue;
    }
}
