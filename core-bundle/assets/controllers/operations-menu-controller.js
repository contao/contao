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
            menuLinkSelector: 'a,button,img,hr',
        });

        this.controllerTarget?.addEventListener('accessibleMenuExpand', () => {
            for (const menu of Object.values(window.AccessibleMenu.menus)) {
                if (menu !== this.$menu && menu.elements.submenuToggles[0].isOpen) {
                    menu.elements.submenuToggles[0].close();
                }
            }

            this.setPosition();
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

        if (this.$menu.elements.submenuToggles[0].isOpen) {
            this.$menu.elements.submenuToggles[0].close();
            return;
        }

        event.preventDefault();
        event.stopPropagation();

        // Prevent accessible-menu from handling pointerup and closing the menu again (see #8065)
        this.element.addEventListener('pointerup', (e) => e.stopPropagation(), { once: true });

        this.$menu.elements.submenuToggles[0].open();
        this.setPosition(event);
    }

    setPosition(event) {
        const offset = 2; // border-width that is excluded from getBoundingClientRect

        const submenuRect = this.submenuTarget.getBoundingClientRect();
        const parentRect = this.controllerTarget.offsetParent.getBoundingClientRect();

        if (event === undefined) {
            this.submenuTarget.style.top = '100%';
            this.submenuTarget.style.right = 'auto';
            this.submenuTarget.style.left = `-${submenuRect.width - parentRect.width - offset}px`;

            return;
        }

        const { innerWidth, innerHeight } = window;
        const rowRect = this.element.getBoundingClientRect();

        const x = innerWidth - event.clientX - (innerWidth - parentRect.left);
        const y = event.clientY - rowRect.top - (parentRect.top - rowRect.top);

        const overflowRight = innerWidth < event.clientX + submenuRect.width + parentRect.width;
        const overflowBottom = innerHeight < event.clientY + submenuRect.height;

        this.submenuTarget.style.left = overflowRight ? `-${x + submenuRect.width - offset}px` : `-${x}px`;
        this.submenuTarget.style.top = overflowBottom ? `${y - submenuRect.height}px` : `${y}px`;

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
