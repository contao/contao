import { Controller } from '@hotwired/stimulus';
import AccessibleMenu from 'accessible-menu';

let menus = [];

export default class OperationsMenuController extends Controller {
    #onPointerUp;
    #onMenuExpand = null;
    #contextMenuEventPosition = null;
    #operationsPromise = null;
    #operationsLoaded = false;

    static targets = ['operations', 'menu', 'submenu', 'controller', 'title'];

    static values = {
        recordId: String,
        recordTable: String,
        primaryOnly: Boolean,
    };

    connect() {
        if (!this.hasControllerTarget || !this.hasMenuTarget) {
            return;
        }

        this.#initializeMenu();
    }

    disconnect() {
        this.#destroyMenu();
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

    controllerTargetConnected(el) {
        // Native pointerup listener to handle long-press cases where the browser suppresses click and the accessible menu does not register open states correctly
        this.#onPointerUp = () => el.click();
        el.addEventListener('pointerup', this.#onPointerUp);

        this.#onMenuExpand = () => {
            for (const menu of menus) {
                if (menu !== this.$menu && menu.elements.submenuToggles[0].isOpen) {
                    menu.elements.submenuToggles[0].close();
                }
            }

            this.#setPosition();
        };

        el.addEventListener('accessibleMenuExpand', this.#onMenuExpand);
    }

    controllerTargetDisconnected(el) {
        el.removeEventListener('pointerup', this.#onPointerUp);
        el.removeEventListener('accessibleMenuExpand', this.#onMenuExpand);
        this.#onMenuExpand = null;
    }

    preload() {
        this.#loadOperationsIfNeeded();
    }

    open(event) {
        if (!this.hasControllerTarget || !this.hasMenuTarget) {
            return;
        }

        const needsLazyLoad = this.primaryOnlyValue && !this.#operationsLoaded && this.hasRecordIdValue;

        if (!needsLazyLoad && this.#isInteractive(event)) {
            return;
        }

        if (needsLazyLoad) {
            event.preventDefault();
        }

        this.#loadOperationsIfNeeded().then((loaded) => {
            if (!loaded || !this.$menu) {
                return;
            }

            // Only open the native context from within the opened operations menu (see #9805)
            if (event.target === this.submenuTarget) {
                return;
            }

            const posX = event.clientX;
            const posY = event.clientY;

            // Open the native context menu when clicking the same position
            if (posX === this.#contextMenuEventPosition?.x && posY === this.#contextMenuEventPosition?.y) {
                this.$menu.elements.submenuToggles[0].close();
                this.#contextMenuEventPosition = null;

                return;
            }

            event.preventDefault();

            // Prevent accessible-menu from handling pointerup and closing the menu again (see #8065, #8567)
            this.element.addEventListener('pointerup', (e) => e.stopPropagation(), { once: true });

            this.#contextMenuEventPosition = { x: posX, y: posY };
            this.$menu.elements.submenuToggles[0].open();
            this.#setPosition(event);
        });
    }

    close() {
        if (!this.hasControllerTarget || !this.hasMenuTarget || !this.$menu) {
            return;
        }

        if (this.$menu.elements.submenuToggles[0].isOpen) {
            this.$menu.elements.submenuToggles[0].close();
            this.#contextMenuEventPosition = null;
        }
    }

    #setPosition(event) {
        this.#resetPosition();

        const offset = 2; // border-width that is excluded from getBoundingClientRect

        const submenuRect = this.submenuTarget.getBoundingClientRect();
        const parentRect = this.menuTarget.querySelector('.operations-menu-container').getBoundingClientRect();

        const rect = this.controllerTarget.getBoundingClientRect();
        let clientX;
        let clientY;

        if (event?.type === 'contextmenu') {
            clientX = event.clientX;
            clientY = event.clientY;
        } else {
            clientX = rect.right;
            clientY = rect.bottom;
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

    #resetPosition() {
        this.submenuTarget.style.removeProperty('top');
        this.submenuTarget.style.removeProperty('right');
        this.submenuTarget.style.removeProperty('left');
    }

    #isInteractive(event) {
        if (event.type !== 'contextmenu') {
            return false;
        }

        const el = event.target;

        return (
            el instanceof HTMLAnchorElement ||
            el instanceof HTMLButtonElement ||
            el instanceof HTMLInputElement ||
            el?.closest('a, button, input')
        );
    }

    async #loadOperationsIfNeeded() {
        if (!this.primaryOnlyValue || this.#operationsLoaded || !this.hasRecordIdValue) {
            return true;
        }

        if (!this.hasOperationsTarget) {
            return false;
        }

        if (this.#operationsPromise) {
            return this.#operationsPromise;
        }

        this.#operationsPromise = (async () => {
            this.element.style.cursor = 'progress';
            this.controllerTarget.style.cursor = 'progress';

            const headers = {
                'Contao-Operations': String(this.recordIdValue),
            };

            if (this.hasRecordTableValue && this.recordTableValue) {
                headers['Contao-Operations-Table'] = this.recordTableValue;
            }

            try {
                const response = await fetch(window.location.href, { headers, credentials: 'same-origin' });

                if (!response.ok) {
                    return false;
                }

                this.#destroyMenu();
                this.operationsTarget.outerHTML = await response.text();
                this.#operationsLoaded = true;
                this.#initializeMenu();
            } catch {
                return false;
            } finally {
                this.element.style.removeProperty('cursor');
                this.controllerTarget.style.removeProperty('cursor');
            }

            return true;
        })();

        return this.#operationsPromise;
    }

    #initializeMenu() {
        if ((this.primaryOnlyValue && !this.#operationsLoaded) || this.$menu) {
            return;
        }

        this.$menu = new AccessibleMenu.DisclosureMenu({
            menuElement: this.menuTarget,
            menuLinkSelector: 'a,button,img',
            // Use arrays to bypass accessible-menu's string class selector validation
            openClass: ['show'],
            closeClass: [],
            transitionClass: [],
        });

        menus.push(this.$menu);
    }

    #destroyMenu() {
        if (!this.$menu) {
            return;
        }

        // Cleanup menu instance, otherwise we would leak memory
        for (const [key, value] of Object.entries(window.AccessibleMenu?.menus ?? {})) {
            if (value === this.$menu) {
                delete window.AccessibleMenu.menus[key];
            }
        }

        menus = menus.filter((menu) => menu !== this.$menu);
        this.$menu = null;
    }
}
