import { Controller } from '@hotwired/stimulus';
import AccessibleMenu from 'accessible-menu';

export default class extends Controller {
    static targets = ['menu', 'controller'];

    initialize () {
        this.close = this.close.bind(this);
    }

    connect () {
        if (!this.hasMenuTarget) {
            return;
        }

        document.addEventListener('pointerdown', this.close);
        this.controllerTarget?.addEventListener('pointerdown', () => { this.setFixedPosition() });

        this.$menu = new AccessibleMenu.DisclosureMenu({
            menuElement: this.menuTarget,
            containerElement: this.element,
            controllerElement: this.controllerTarget,
        });

        this.$menu.dom.controller.addEventListener('accessibleMenuExpand', () => {
            this.element.classList.add('hover');
        });

        this.$menu.dom.controller.addEventListener('accessibleMenuCollapse', () => {
            this.element.classList.remove('hover');
        });
    }

    disconnect () {
        document.removeEventListener('pointerdown', this.close);
    }

    open (event) {
        if (!this.hasMenuTarget) {
            return;
        }

        event.preventDefault();
        event.stopPropagation();

        this.$menu.elements.controller.preview();
        this.setFixedPosition(event);
    }

    close (event) {
        if (this.$menu && !this.menuTarget.contains(event.target) && !this.controllerTarget?.contains(event.target)) {
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
}
