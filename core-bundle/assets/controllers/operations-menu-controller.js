import { Controller } from '@hotwired/stimulus';
import AccessibleMenu from 'accessible-menu';

export default class OperationsMenuController extends Controller {
    static instances = [];
    static targets = ['menu', 'controller', 'title'];

    initialize () {
        OperationsMenuController.instances.push(this);
    }

    connect () {
        if (!this.hasMenuTarget) {
            return;
        }

        this.controllerTarget?.addEventListener('pointerdown', () => { this.setFixedPosition() });

        this.$menu = new AccessibleMenu.DisclosureMenu({
            menuElement: this.menuTarget,
            containerElement: this.element,
            controllerElement: this.controllerTarget,
        });

        this.$menu.dom.controller.addEventListener('accessibleMenuExpand', () => {
            this._others('close');
            this.setFixedPosition();
            this.element.classList.add('hover');
        });

        this.$menu.dom.controller.addEventListener('accessibleMenuCollapse', () => {
            this.element.classList.remove('hover');
        });
    }

    titleTargetConnected (el) {
        el.removeAttribute(`data-${this.identifier}-target`);

        const link = el.querySelector('a[title]');
        if (link && '' !== link.getAttribute('title')) {
            link.append(link.getAttribute('title'));
            return;
        }

        const img = el.querySelector('img[alt]');
        if (img && '' !== img.getAttribute('alt')) {
            img.parentNode.append(img.getAttribute('alt'));
        }
    }

    open (event) {
        if (!this.hasMenuTarget || this.isInteractive(event.target)) {
            return;
        }

        event.preventDefault();
        event.stopPropagation();

        this.$menu.elements.controller.preview();
        this.setFixedPosition(event);
    }

    close () {
        if (this.$menu) {
            this.$menu.elements.controller.close();
        }
    }

    setFixedPosition (event) {
        const rect = this.menuTarget.getBoundingClientRect();
        let x, y, offset = 0;

        if (event) {
            x = event.clientX;
            y = event.clientY;
        } else {
            const r = this.controllerTarget.getBoundingClientRect();
            x = r.x;
            y = r.y;
            offset = 20;
        }

        this.menuTarget.style.position = 'fixed';
        this.menuTarget.style.right = 'auto';

        if (window.innerHeight < y + rect.height) {
            this.menuTarget.style.top = `${y - rect.height}px`;
        } else {
            this.menuTarget.style.top = `${y + offset}px`;
        }

        if (window.innerWidth < x + rect.width) {
            this.menuTarget.style.left = `${x - rect.width + offset}px`;
        } else {
            this.menuTarget.style.left = `${x + offset}px`;
        }
    }

    isInteractive (el) {
        let node = el.nodeName.toLowerCase();

        if ('a' === node || 'button' === node || 'input' === node) {
            return true;
        }

        // Also check the parent element if el is not interactive
        node = el.parentElement.nodeName.toLowerCase();

        return 'a' === node || 'button' === node || 'input' === node;
    }

    _others(fn, args = []) {
        OperationsMenuController.instances.forEach(instance => {
            if(instance !== this) {
                instance[fn](...args);
            }
        })
    }
}
