import { Controller } from '@hotwired/stimulus';
import AccessibleMenu from 'accessible-menu';

export default class OperationsMenuController extends Controller {
    static targets = ['menu', 'submenu', 'controller', 'title'];

    connect() {
        if (!this.hasControllerTarget || !this.hasMenuTarget) {
            return;
        }

        this.$menu = new AccessibleMenu.DisclosureMenu({
            menuElement: this.menuTarget,
            menuLinkSelector: 'a,button,img',
        });

        this.controllerTarget?.addEventListener('accessibleMenuExpand', () => {
            Object.values(window.AccessibleMenu.menus).forEach((menu) => {
                if (menu !== this.$menu && menu.elements.submenuToggles[0].isOpen) {
                    menu.elements.submenuToggles[0].close();
                }
            });

            this.setFixedPosition();
            this.element.classList.add('hover');
        });

        this.controllerTarget?.addEventListener('accessibleMenuCollapse', () => {
            this.element.classList.remove('hover');
        });
    }

    disconnect() {
        // Cleanup menu instance, otherwise we would leak memory
        for (const [key, value] of Object.entries(window.AccessibleMenu?.menus ?? {})) {
            if (value === this.$menu) {
                delete window.AccessibleMenu.menus[key];
            }
        }
    }

    titleTargetConnected(el) {
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

    open(event) {
        if (!this.hasControllerTarget || !this.hasMenuTarget || this.isInteractive(event.target)) {
            return;
        }

        event.preventDefault();
        event.stopPropagation();

        this.$menu.elements.submenuToggles[0].open();
        this.setFixedPosition(event);
    }

    setFixedPosition(event) {
        const rect = this.submenuTarget.getBoundingClientRect();
        let x,
            y,
            offset = 0;

        if (event) {
            x = event.clientX;
            y = event.clientY;
        } else {
            const r = this.controllerTarget.getBoundingClientRect();
            x = r.x;
            y = r.y;
            offset = 20;
        }

        this.submenuTarget.style.position = 'fixed';
        this.submenuTarget.style.right = 'auto';

        if (window.innerHeight < y + rect.height) {
            this.submenuTarget.style.top = `${y - rect.height}px`;
        } else {
            this.submenuTarget.style.top = `${y + offset}px`;
        }

        if (window.innerWidth < x + rect.width) {
            this.submenuTarget.style.left = `${x - rect.width + offset}px`;
        } else {
            this.submenuTarget.style.left = `${x + offset}px`;
        }
    }

    isInteractive(el) {
        return (
            el instanceof HTMLAnchorElement ||
            el instanceof HTMLButtonElement ||
            el instanceof HTMLInputElement ||
            el?.closest('a, button, input')
        );
    }
}
