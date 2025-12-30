export class Navigation {
    constructor(options) {
        this.options = this.#merge({
            selector: '.navigation-main',
            toggle: '.nav-burger',
            minWidth: 1024,
            classes: {
                submenuButton: 'btn-toggle-submenu',
                expand: 'nav-expanded',
                active: 'is-active',
                bodyOpen: 'navigation-open',
                boundsRight: 'bounds-right',
                boundsLeft: 'bounds-left',
            },
            ariaLabels: {
                'expand': 'Expand menu: ',
                'collapse': 'Collapse menu: ',
            }
        }, options || {});

        this.navigation = document.querySelector(this.options.selector);
        this.toggle = document.querySelector(this.options.toggle);

        if (!this.navigation) {
            return;
        }

        this.state = false;

        this.dropdowns = [];
        this.active = [];
        this.listeners = new WeakMap();

        this.#init();

        for (const dropdown of this.dropdowns) {
            this.#addSubMenuButton(dropdown);
        }

        new ResizeObserver(() => {
            const isDesktop = this.#isDesktop();

            for (const dropdown of this.dropdowns) {
                isDesktop ? this.#registerDropdownEvents(dropdown) : this.#unregisterDropdownEvents(dropdown);
            }
        }).observe(document.body);
    }

    /**
     * Handles the focus trap on the open mobile navigation
     */
    focusTrapEvent(event) {
        if (!(event.key === 'Tab' || event.keyCode === 9)) {
            return;
        }

        if (document.activeElement === this.lastFocus && !event.shiftKey) {
            event.preventDefault();
            this.firstFocus?.focus();
        }

        if (document.activeElement === this.firstFocus && event.shiftKey) {
            event.preventDefault();
            this.lastFocus?.focus();
        }
    }

    /**
     * Merges configuration options and replaces them if they exist
     */
    #merge(a, b) {
        return [...new Set([...Object.keys(a), ...Object.keys(b)])].reduce((result, key) => ({
            ...result,
            [key]: "object" === typeof (a[key]) ? Object.assign({}, a[key], b[key]) : !b[key] ? a[key] : b[key],
        }), {});
    }

    /**
     * Placeholder button that is cloned for each submenu item
     */
    #createSubMenuButton() {
        this.btn = document.createElement('button');
        this.btn.classList.add(this.options.classes.submenuButton);
        this.btn.ariaHasPopup = 'true';
        this.btn.ariaExpanded = 'false';
    }

    /**
     * Determines the focus trap targets
     */
    #initFocusTrapTargets() {
        const nodes = [this.navigation.closest('#header')?.querySelector('a[href].logo'), ...this.navigation.querySelectorAll('a[href]:not([disabled]), button:not([disabled])')];

        this.firstFocus = nodes[0] ?? this.toggle;
        this.lastFocus = nodes[nodes.length - 1] ?? [];
    }

    /**
     * Toggles the menu state on mobile
     */
    #toggleMenuState() {
        this.toggle.ariaExpanded = this.state ? 'false' : 'true';
        this.toggle.classList.toggle(this.options.classes.active, !this.state);
        this.navigation.classList.toggle(this.options.classes.active, !this.state);

        document.body.classList.toggle(this.options.classes.bodyOpen, !this.state);

        this.state = !this.state;
    }

    /**
     * Adds and removes the focusTrap based on the mobile navigation state
     */
    #focusMenu() {
        if (this.state) {
            document.addEventListener('keydown', this.focusTrapEvent, false);
        } else {
            document.removeEventListener('keydown', this.focusTrapEvent, false);
        }
    }

    /**
     * Initializes navigation items and sets aria-attributes if they do not exist
     */
    #init() {
        this.#createSubMenuButton();
        this.#initMobileToggleEvents();

        this.navigation.querySelectorAll('li').forEach(item => {

            if (item.classList.contains('submenu')) {
                this.dropdowns.push(item);
            }

            const navItem = item.firstElementChild;

            if (navItem.classList.contains('active')) {
                navItem.ariaCurrent = 'page';
            }

            if (!navItem.ariaLabel && navItem.title) {
                navItem.ariaLabel = navItem.title;
                navItem.removeAttribute('title');
            }
        });

        // Hide the active navigation on escape
        document.addEventListener('keyup', (e) => {
            this.#isDesktop() && e.key === 'Escape' && this.#hideDropdown();
        });
    }

    /**
     * Updates the aria labels and state for the dropdown buttons
     */
    #updateAriaState(dropdown, show) {
        dropdown.btn.ariaLabel = (show ? this.options.ariaLabels.collapse : this.options.ariaLabels.expand) + dropdown.btn.dataset.label;
        dropdown.btn.ariaExpanded = show ? 'true' : 'false';
    }

    /**
     * Collapses the dropdown
     */
    #collapseSubmenu(dropdown) {
        dropdown.classList.remove(this.options.classes.expand);

        dropdown.querySelector(':scope > ul')?.classList.remove(
            this.options.classes.boundsLeft,
            this.options.classes.boundsRight,
        );

        this.#updateAriaState(dropdown, false);
    }

    /**
     * Handles hiding dropdowns. Adding no parameter will close all
     */
    #hideDropdown(dropdown = null) {
        if (0 === this.active.length) {
            return;
        }

        // Case 1: Leaving the previous dropdown (e.g. focus left)
        if (this.active.includes(dropdown)) {
            this.#collapseSubmenu(dropdown);
            this.active = this.active.filter(node => node !== dropdown);
        }

        // Case 2: Not contained in the tree at all, remove everything
        else if (null === dropdown || this.active[0] !== dropdown && !this.active[0].contains(dropdown)) {
            this.active.forEach(node => this.#collapseSubmenu(node));
            this.active = [];
        }

        // Case 3: Down the drain with everything that ain't a parent node :)
        else {
            this.active = this.active.filter(node => {
                if (node.contains(dropdown)) {
                    return true;
                }

                this.#collapseSubmenu(node);
                return false;
            });
        }
    }

    #setDropdownPosition(dropdown) {
        const submenu = dropdown.querySelector(':scope > ul');

        if (null === submenu) {
            return;
        }

        if (submenu.getBoundingClientRect().right >= window.innerWidth) {
            submenu.classList.add(this.options.classes.boundsRight);
        } else if (submenu.getBoundingClientRect().left < 0) {
            submenu.classList.add(this.options.classes.boundsLeft);
        }
    }

    /**
     * Shows the dropdown
     */
    #showDropdown(dropdown) {
        this.#hideDropdown(dropdown);

        dropdown.classList.add(this.options.classes.expand);

        if (this.#isDesktop()) {
            this.#setDropdownPosition(dropdown);
        }

        this.#updateAriaState(dropdown, true);

        if (!this.active.includes(dropdown)) {
            this.active.push(dropdown);
        }
    }

    /**
     * Updates the dropdown state
     */
    #toggleDropdownState(dropdown, show) {
        show ? this.#showDropdown(dropdown) : this.#hideDropdown(dropdown);
    }

    /**
     * Adds a submenu button that toggles submenu navigations
     */
    #addSubMenuButton(dropdown) {
        const item = dropdown.firstElementChild,
              btn = this.btn.cloneNode();

        dropdown.btn = btn;

        btn.dataset.label = item.textContent;
        btn.ariaLabel = this.options.ariaLabels.expand + item.textContent;

        btn.addEventListener('click', () => {
            const show = btn.ariaExpanded === 'false' ?? true;
            this.#toggleDropdownState(dropdown, show);
        });

        item.after(btn);
    }

    /**
     * Mouse enter event for dropdowns
     */
    #mouseEnter(e, dropdown) {
        this.#toggleDropdownState(dropdown, true);
    }

    /**
     * Mouse leave event for dropdowns
     */
    #mouseLeave(e, dropdown) {
        this.#hideDropdown(dropdown);
    }

    /**
     * Listener for the focusout event when an element loses it's focus, necessary for tab control
     */
    #focusOut(e, dropdown) {
        if (e.relatedTarget && this.active.length > 0 && !dropdown.contains(e.relatedTarget)) {
            this.#hideDropdown(dropdown);
        }
    }

    #isDesktop() {
        return window.innerWidth >= this.options.minWidth;
    }

    #initMobileToggleEvents() {
        this.#initFocusTrapTargets();
        this.focusTrapEvent = this.focusTrapEvent.bind(this);

        this.toggle?.addEventListener('click', () => {
            if (this.#isDesktop()) {
                return;
            }

            this.#toggleMenuState();
            this.#focusMenu();
        });
    }

    /**
     * Registers the mouse dropdown events
     */
    #registerDropdownEvents(dropdown) {
        if (this.listeners.has(dropdown)) {
            return;
        }

        const events = {
            mouseenter: (e) => this.#mouseEnter(e, dropdown),
            mouseleave: (e) => this.#mouseLeave(e, dropdown),
            focusout: (e) => this.#focusOut(e, dropdown),
        }

        for(const [type, event] of Object.entries(events)) {
            dropdown.addEventListener(type, event);
        }

        this.listeners.set(dropdown, events);
    }

    /**
     * Removes the mouse dropdown events
     */
    #unregisterDropdownEvents(dropdown) {
        const events = this.listeners.get(dropdown);

        if (!events) {
            return;
        }

        for (const [type, event] of Object.entries(events)) {
            dropdown.removeEventListener(type, event);
        }

        this.listeners.delete(dropdown);
    }
}
