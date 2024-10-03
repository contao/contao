import { Controller } from '@hotwired/stimulus';
import * as Turbo from '@hotwired/turbo';

export default class extends Controller {
    static targets = ['primary', 'secondary'];

    static afterLoad (identifier, application) {
        const setupController = () => {
            document.querySelectorAll('.click2edit').forEach((el) => {
                el.classList.remove('click2edit');

                const primary = el.querySelector('a.edit');
                const secondary = el.querySelector('a.children');

                if (primary) {
                    primary.setAttribute(`data-${identifier}-target`, "primary");
                }

                if (secondary) {
                    secondary.setAttribute(`data-${identifier}-target`, "secondary");
                }

                el.dataset.controller = `${el.dataset.controller || ''} ${identifier}`;
            });
        };

        document.addEventListener('DOMContentLoaded', setupController);
        document.addEventListener('ajax_change', setupController);
        document.addEventListener('turbo:render', setupController);
        document.addEventListener('turbo:frame-render', setupController);
        setupController();

        Theme.setupCtrlClick = () => {
            console.warn('Using Theme.setupCtrlClick() is deprecated and will be removed in Contao 6. Apply the Stimulus actions instead.');
            setupController();
        }
    }

    initialize () {
        this.handle = this.handle.bind(this);
    }

    connect () {
        this.element.addEventListener('click', this.handle);
    }

    disconnect () {
        this.element.removeEventListener('click', this.handle);
    }

    handle (event) {
        // Ignore clicks on anchor elements
        if (!this.isValid(event.target)) {
            return;
        }

        const primaryKey = window.navigator.platform?.startsWith('Mac') ? 'metaKey' : 'ctrlKey';

        if (event[primaryKey] && this.hasPrimaryTarget && this.primaryTarget.href) {
            Turbo.visit(this.primaryTarget.href);
            return;
        }

        if (event.shiftKey && this.hasSecondaryTarget && this.secondaryTarget.href) {
            Turbo.visit(this.secondaryTarget.href);
            return;
        }

        if (event.pointerType === 'mouse' || !this.hasPrimaryTarget || !this.primaryTarget.href) {
            return;
        }

        if (!this.element.getAttribute('data-visited')) {
            this.element.setAttribute('data-visited', '1');
        } else {
            this.element.removeAttribute('data-visited');
            Turbo.visit(this.primaryTarget.href);
        }
    }

    visitPrimary (event) {
        if (this.hasPrimaryTarget && this.primaryTarget.href && this.isValid(event.target)) {
            Turbo.visit(this.primaryTarget.href);
        }
    }

    visitSecondary (event) {
        if (this.hasSecondaryTarget && this.secondaryTarget.href && this.isValid(event.target)) {
            Turbo.visit(this.secondaryTarget.href);
        }
    }

    isValid (element) {
        return element.tagName !== 'a' && !element.closest('a');
    }
}
