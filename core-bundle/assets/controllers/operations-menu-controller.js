import { Controller } from '@hotwired/stimulus';
import AccessibleMenu from 'accessible-menu';

const menus = [];

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

        menus.push(this.$menu);

        this.controllerTarget?.addEventListener('accessibleMenuExpand', () => {
            for (const menu of menus) {
                if (menu !== this.$menu && menu.elements.submenuToggles[0].isOpen) {
                    menu.elements.submenuToggles[0].close();
                }
            }

            this.setPosition();
        });
    }

    disconnect() {
        // Cleanup menu instance, otherwise we would leak memory
        for (const [key, value] of Object.entries(window.AccessibleMenu?.menus ?? {})) {
            if (value === this.$menu) {
                delete window.AccessibleMenu.menus[key];
            }
        }

        delete menus[menus.findIndex((menu) => menu === this.$menu)];
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

        if (this.$menu.elements.submenuToggles[0].isOpen) {
            this.$menu.elements.submenuToggles[0].close();
            return;
        }

        event.preventDefault();

        this.$menu.elements.submenuToggles[0].open();
        this.setPosition(event);
    }

    setPosition(event) {
        const offset = 2; // border-width that is excluded from getBoundingClientRect

        const submenuRect = this.submenuTarget.getBoundingClientRect();
        const parentRect = this.menuTarget.querySelector('.operations-menu-container').getBoundingClientRect();

        const rect = this.controllerTarget.getBoundingClientRect();
        let clientX;
        let clientY;

        if (event === undefined) {
            clientX = rect.right;
            clientY = rect.bottom;
        } else {
            clientX = event.clientX;
            clientY = event.clientY;
        }

        const { innerWidth, innerHeight } = window;
        const rowRect = this.element.getBoundingClientRect();

        const overflowRight = innerWidth < clientX + submenuRect.width + parentRect.width;
        const overflowBottom = innerHeight < clientY + submenuRect.height;

        const x = innerWidth - clientX - (innerWidth - parentRect.left);
        let y = clientY - rowRect.top - (parentRect.top - rowRect.top);

        // If not a context menu and bottom overflow, position at the top of the "more" handle.
        if (event === undefined && overflowBottom) {
            y = y - clientY + rect.top - offset;
        }

        this.submenuTarget.style.left = overflowRight ? `-${x + submenuRect.width - offset}px` : `-${x}px`;
        this.submenuTarget.style.top = overflowBottom ? `${y - submenuRect.height + offset}px` : `${y}px`;
        this.submenuTarget.style.right = 'auto';
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
