import { Controller } from '@hotwired/stimulus';

let openMenu = null;

// Register one listener for the menu instead of multiple separate ones for each controller
document.addEventListener('click', (event) => {
    openMenu?.documentClick(event);
});

document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape') {
        openMenu?.close();
    }
});

export default class OperationsMenuController extends Controller {
    #menuId = null;
    #skipClickEvent = false;
    #contextMenuEventPosition = null;
    #operationsPromise = null;
    #operationsLoaded = false;

    static targets = ['operations', 'menu', 'submenu', 'controller', 'title'];

    static values = {
        recordId: String,
        recordTable: String,
        primaryOnly: Boolean,
    };

    initialize() {
        this.#menuId = `menu-${(Math.random() + 1).toString(36).substring(7)}`;
    }

    controllerTargetConnected() {
        this.controllerTarget.id = this.#controllerTargetId;
        this.controllerTarget.setAttribute('aria-controls', this.#menuId);
        this.controllerTarget.setAttribute('aria-expanded', 'false');
    }

    submenuTargetConnected() {
        this.submenuTarget.id = this.#menuId;
        this.submenuTarget.setAttribute('aria-labelledby', this.#controllerTargetId);
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

    preload() {
        this.#loadOperationsIfNeeded();
    }

    toggle(event) {
        if (event.type === 'pointerup') {
            this.#skipClickEvent = true;
            // skipClickEvent will be reset in the next cycle but will still prevent the click event
            setTimeout(() => (this.#skipClickEvent = false), 0);
        }

        if (event.type === 'click' && this.#skipClickEvent) {
            this.#skipClickEvent = false;
            return;
        }

        this.#isOpen ? this.#closeMenu() : this.#openMenu(event);
    }

    open(event) {
        if (!this.hasControllerTarget || !this.hasMenuTarget || this.#isInteractive(event)) {
            return;
        }

        // Needs lazyload
        if (this.primaryOnlyValue && !this.#operationsLoaded && this.hasRecordIdValue) {
            event.preventDefault();
        }

        this.#openMenu(event, () => {
            // Only open the native context from within the opened operations menu (see #9805)
            if (event.target === this.submenuTarget) {
                return false;
            }

            const posX = event.clientX;
            const posY = event.clientY;

            // Open the native context menu when clicking the same position
            if (posX === this.#contextMenuEventPosition?.x && posY === this.#contextMenuEventPosition?.y) {
                this.#contextMenuEventPosition = null;
                return false;
            }

            event.preventDefault();

            this.#contextMenuEventPosition = { x: posX, y: posY };

            return true;
        });
    }

    close() {
        if (!this.hasControllerTarget || !this.hasMenuTarget) {
            return;
        }

        this.#closeMenu();
        this.#contextMenuEventPosition = null;
    }

    documentClick(event) {
        if (
            !this.#isOpen ||
            this.submenuTarget.contains(event.target) ||
            this.controllerTarget.contains(event.target)
        ) {
            return;
        }

        this.#closeMenu();
    }

    #openMenu(event, onBeforeOpenCallback = null) {
        this.#loadOperationsIfNeeded().then((loaded) => {
            if (!loaded) {
                return;
            }

            if (onBeforeOpenCallback && !onBeforeOpenCallback()) {
                return;
            }

            if (openMenu && openMenu !== this) {
                openMenu.#closeMenu();
            }

            openMenu = this;
            this.#setState(true);
            this.#setPosition(event);
        });
    }

    #closeMenu() {
        openMenu = null;
        this.#setState(false);
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

        this.#operationsPromise ??= this.#fetchOperations();

        return this.#operationsPromise;
    }

    async #fetchOperations() {
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

            this.operationsTarget.outerHTML = await response.text();
            this.#operationsLoaded = true;
        } catch {
            return false;
        } finally {
            this.element.style.removeProperty('cursor');
            this.controllerTarget.style.removeProperty('cursor');
        }

        return true;
    }

    #setState(state) {
        this.controllerTarget.setAttribute('aria-expanded', String(state));
        this.submenuTarget.classList.toggle('show', state);
    }

    get #isOpen() {
        return this.controllerTarget.ariaExpanded === 'true';
    }

    get #controllerTargetId() {
        return `menu-button-${this.#menuId}`;
    }
}
