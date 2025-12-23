export class Navigation {
    constructor(options) {
        this.options = this._merge({
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

        this._init();

        for (const dropdown of this.dropdowns) {
            this._addSubMenuButton(dropdown);
        }

        new ResizeObserver(() => {
            const isDesktop = this._isDesktop();

            for (const dropdown of this.dropdowns) {
                isDesktop ? this._registerDropdownEvents(dropdown) : this._unregisterDropdownEvents(dropdown);
            }
        }).observe(document.body);
    }

    /**
     * Merges configuration options and replaces them if they exist
     *
     * @private
     */
    _merge(a, b) {
        return [...new Set([...Object.keys(a), ...Object.keys(b)])].reduce((result, key) => ({
            ...result,
            [key]: "object" === typeof (a[key]) ? Object.assign({}, a[key], b[key]) : !b[key] ? a[key] : b[key],
        }), {});
    }

    /**
     * Placeholder button that is cloned for each submenu item
     *
     * @private
     */
    _createSubMenuButton() {
        this.btn = document.createElement('button');
        this.btn.classList.add(this.options.classes.submenuButton);
        this.btn.ariaHasPopup = 'true';
        this.btn.ariaExpanded = 'false';
    }

    /**
     * Determines the focus trap targets
     *
     * @private
     */
    _initFocusTrapTargets() {
        const nodes = [this.navigation.closest('#header')?.querySelector('a[href].logo'), ...this.navigation.querySelectorAll('a[href]:not([disabled]), button:not([disabled])')];

        this.firstFocus = nodes[0] ?? this.toggle;
        this.lastFocus = nodes[nodes.length - 1] ?? [];
    }

    /**
     * Handles the focus trap on the open mobile navigation
     *
     * @private
     */
    _focusTrapEvent(event) {
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
     * Toggles the menu state on mobile
     *
     * @private
     */
    _toggleMenuState() {
        this.toggle.ariaExpanded = this.state ? 'false' : 'true';
        this.toggle.classList.toggle(this.options.classes.active, !this.state);
        this.navigation.classList.toggle(this.options.classes.active, !this.state);

        document.body.classList.toggle(this.options.classes.bodyOpen, !this.state);

        this.state = !this.state;
    }

    /**
     * Adds and removes the focusTrap based on the mobile navigation state
     *
     * @private
     */
    _focusMenu() {
        if (this.state) {
            document.addEventListener('keydown', this._focusTrapEvent, false);
        } else {
            document.removeEventListener('keydown', this._focusTrapEvent, false);
        }
    }

    /**
     * Initializes navigation items and sets aria-attributes if they do not exist
     *
     * @private
     */
    _init() {
        this._createSubMenuButton();
        this._initMobileToggleEvents();

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
            e.key === 'Escape' && this._hideDropdown();
        });
    }

    /**
     * Updates the aria labels and state for the dropdown buttons
     *
     * @private
     */
    _updateAriaState(dropdown, show) {
        dropdown.btn.ariaLabel = (show ? this.options.ariaLabels.collapse : this.options.ariaLabels.expand) + dropdown.btn.dataset.label;
        dropdown.btn.ariaExpanded = show ? 'true' : 'false';
    }

    /**
     * Collapses the dropdown
     *
     * @private
     */
    _collapseSubmenu(dropdown) {
        dropdown.classList.remove(this.options.classes.expand);

        dropdown.querySelector(':scope > ul')?.classList.remove(
            this.options.classes.boundsLeft,
            this.options.classes.boundsRight,
        );

        this._updateAriaState(dropdown, false);
    }

    /**
     * Handles hiding dropdowns. Adding no parameter will close all
     *
     * @private
     */
    _hideDropdown(dropdown = null) {
        if (0 === this.active.length) {
            return;
        }

        // Case 1: Leaving the previous dropdown (e.g. focus left)
        if (this.active.includes(dropdown)) {
            this._collapseSubmenu(dropdown);
            this.active = this.active.filter(node => node !== dropdown);
        }

        // Case 2: Not contained in the tree at all, remove everything
        else if (null === dropdown || this.active[0] !== dropdown && !this.active[0].contains(dropdown)) {
            this.active.forEach(node => this._collapseSubmenu(node));
            this.active = [];
        }

        // Case 3: Down the drain with everything that ain't a parent node :)
        else {
            this.active = this.active.filter(node => {
                if (node.contains(dropdown)) {
                    return true;
                }

                this._collapseSubmenu(node);
                return false;
            });
        }
    }

    _setDropdownPosition(dropdown) {
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
     *
     * @private
     */
    _showDropdown(dropdown) {
        this._hideDropdown(dropdown);

        dropdown.classList.add(this.options.classes.expand);

        if (this._isDesktop()) {
            this._setDropdownPosition(dropdown);
        }

        this._updateAriaState(dropdown, true);

        if (!this.active.includes(dropdown)) {
            this.active.push(dropdown);
        }
    }

    /**
     * Updates the dropdown state
     *
     * @private
     */
    _toggleDropdownState(dropdown, show) {
        show ? this._showDropdown(dropdown) : this._hideDropdown(dropdown);
    }

    /**
     * Adds a submenu button that toggles submenu navigations
     *
     * @private
     */
    _addSubMenuButton(dropdown) {
        const item = dropdown.firstElementChild,
              btn = this.btn.cloneNode();

        dropdown.btn = btn;

        btn.dataset.label = item.textContent;
        btn.ariaLabel = this.options.ariaLabels.expand + item.textContent;

        btn.addEventListener('click', () => {
            const show = btn.ariaExpanded === 'false' ?? true;
            this._toggleDropdownState(dropdown, show);
        });

        item.after(btn);
    }

    /**
     * Mouse enter event for dropdowns
     *
     * @private
     */
    _mouseEnter(e, dropdown) {
        this._toggleDropdownState(dropdown, true);
    }

    /**
     * Mouse leave event for dropdowns
     *
     * @private
     */
    _mouseLeave(e, dropdown) {
        this._hideDropdown(dropdown);
    }

    /**
     * Listener for the focusout event when an element loses it's focus, necessary for tab control
     *
     * @private
     */
    _focusOut(e, dropdown) {
        if (e.relatedTarget && this.active.length > 0 && !dropdown.contains(e.relatedTarget)) {
            this._hideDropdown(dropdown);
        }
    }

    _isDesktop() {
        return window.innerWidth >= this.options.minWidth;
    }

    _initMobileToggleEvents() {
        this._initFocusTrapTargets();
        this._focusTrapEvent = this._focusTrapEvent.bind(this);

        this.toggle?.addEventListener('click', () => {
            if (this._isDesktop()) {
                return;
            }

            this._toggleMenuState();
            this._focusMenu();
        });
    }

    /**
     * Registers the mouse dropdown events
     *
     * @private
     */
    _registerDropdownEvents(dropdown) {
        if (this.listeners.has(dropdown)) {
            return;
        }

        const events = {
            mouseenter: (e) => this._mouseEnter(e, dropdown),
            mouseleave: (e) => this._mouseLeave(e, dropdown),
            focusout: (e) => this._focusOut(e, dropdown),
        }

        for(const [type, event] of Object.entries(events)) {
            dropdown.addEventListener(type, event);
        }

        this.listeners.set(dropdown, events);
    }

    /**
     * Removes the mouse dropdown events
     *
     * @private
     */
    _unregisterDropdownEvents(dropdown) {
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
