import { Controller } from '@hotwired/stimulus';
import AccessibleMenu from 'accessible-menu';

export default class extends Controller {
    static targets = ['menu', 'container', 'controller'];

    initialize () {
        this.close = this.close.bind(this);
    }

    connect () {
        if (!this.hasMenuTarget) {
            return;
        }

        this.menuTarget.addEventListener('contextmenu', e => e.stopPropagation());
        document.addEventListener('mousedown', this.close);

        if (!this.hasControllerTarget) {
            return;
        }

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
        document.removeEventListener('mousedown', this.close);
    }

    contextmenu (event) {
        if (!this.hasMenuTarget) {
            return;
        }

        event.preventDefault();

        this.$contextmenu = this.menuTarget.clone();
        this.$contextmenu.classList.add('contextmenu');
        this.$contextmenu.classList.add('operations-menu');
        this.$contextmenu.classList.add('show');
        this.setFixedPosition(this.$contextmenu, event);

        if (this.element.classList.contains('hover-div')) {
            this.element.classList.add('hover');
        }

        document.body.append(this.$contextmenu);
    }

    close (event) {
        if (this.$menu && !this.element.contains(event.target)) {
            this.$menu.elements.controller.close();
        }

        if (this.$contextmenu && !this.$contextmenu.contains(event.target)) {
            this.$contextmenu.remove();
            this.element.classList.remove('hover');
            delete this.$contextmenu;
        }
    }

    setFixedPosition (element, event) {
        setTimeout(() => {
            const rect = element.getBoundingClientRect();

            element.style.position = 'fixed';

            if (window.innerHeight < event.clientY + rect.height) {
                element.style.top = `${event.clientY - rect.height}px`;
            } else {
                element.style.top = `${event.clientY}px`;
            }

            if (window.innerWidth < window.scrollX + event.clientX + rect.width) {
                element.style.left = `${window.scrollX + event.clientX - rect.width}px`;
            } else {
                element.style.left = `${window.scrollX + event.clientX}px`;
            }
        }, 0);
    }
}
