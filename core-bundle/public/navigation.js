/******/ (function() { // webpackBootstrap
/******/ 	"use strict";
/******/ 	var __webpack_modules__ = ({

/***/ "./core-bundle/assets/scripts/frontend/navigation.js":
/*!***********************************************************!*\
  !*** ./core-bundle/assets/scripts/frontend/navigation.js ***!
  \***********************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   Navigation: function() { return /* binding */ Navigation; }
/* harmony export */ });
class Navigation {
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
        boundsLeft: 'bounds-left'
      },
      ariaLabels: {
        'expand': 'Expand menu: ',
        'collapse': 'Collapse menu: '
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
      for (const dropdown of this.dropdowns) {
        this._isDesktop() ? this._registerDropdownEvents(dropdown) : this._unregisterDropdownEvents(dropdown);
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
      [key]: "object" === typeof a[key] ? Object.assign({}, a[key], b[key]) : !b[key] ? a[key] : b[key]
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
    document.addEventListener('keyup', e => {
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
    dropdown.querySelector(':scope > ul')?.classList.remove(this.options.classes.boundsLeft, this.options.classes.boundsRight);
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
      mouseenter: e => this._mouseEnter(e, dropdown),
      mouseleave: e => this._mouseLeave(e, dropdown),
      focusout: e => this._focusOut(e, dropdown)
    };
    for (const [type, event] of Object.entries(events)) {
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

/***/ }),

/***/ "./core-bundle/assets/styles/frontend/navigation.pcss":
/*!************************************************************!*\
  !*** ./core-bundle/assets/styles/frontend/navigation.pcss ***!
  \************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
// extracted by mini-css-extract-plugin


/***/ })

/******/ 	});
/************************************************************************/
/******/ 	// The module cache
/******/ 	var __webpack_module_cache__ = {};
/******/ 	
/******/ 	// The require function
/******/ 	function __webpack_require__(moduleId) {
/******/ 		// Check if module is in cache
/******/ 		var cachedModule = __webpack_module_cache__[moduleId];
/******/ 		if (cachedModule !== undefined) {
/******/ 			return cachedModule.exports;
/******/ 		}
/******/ 		// Create a new module (and put it into the cache)
/******/ 		var module = __webpack_module_cache__[moduleId] = {
/******/ 			// no module.id needed
/******/ 			// no module.loaded needed
/******/ 			exports: {}
/******/ 		};
/******/ 	
/******/ 		// Execute the module function
/******/ 		__webpack_modules__[moduleId](module, module.exports, __webpack_require__);
/******/ 	
/******/ 		// Return the exports of the module
/******/ 		return module.exports;
/******/ 	}
/******/ 	
/************************************************************************/
/******/ 	/* webpack/runtime/define property getters */
/******/ 	!function() {
/******/ 		// define getter functions for harmony exports
/******/ 		__webpack_require__.d = function(exports, definition) {
/******/ 			for(var key in definition) {
/******/ 				if(__webpack_require__.o(definition, key) && !__webpack_require__.o(exports, key)) {
/******/ 					Object.defineProperty(exports, key, { enumerable: true, get: definition[key] });
/******/ 				}
/******/ 			}
/******/ 		};
/******/ 	}();
/******/ 	
/******/ 	/* webpack/runtime/hasOwnProperty shorthand */
/******/ 	!function() {
/******/ 		__webpack_require__.o = function(obj, prop) { return Object.prototype.hasOwnProperty.call(obj, prop); }
/******/ 	}();
/******/ 	
/******/ 	/* webpack/runtime/make namespace object */
/******/ 	!function() {
/******/ 		// define __esModule on exports
/******/ 		__webpack_require__.r = function(exports) {
/******/ 			if(typeof Symbol !== 'undefined' && Symbol.toStringTag) {
/******/ 				Object.defineProperty(exports, Symbol.toStringTag, { value: 'Module' });
/******/ 			}
/******/ 			Object.defineProperty(exports, '__esModule', { value: true });
/******/ 		};
/******/ 	}();
/******/ 	
/************************************************************************/
var __webpack_exports__ = {};
// This entry needs to be wrapped in an IIFE because it needs to be isolated against other modules in the chunk.
!function() {
/*!******************************************!*\
  !*** ./core-bundle/assets/navigation.js ***!
  \******************************************/
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _scripts_frontend_navigation__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./scripts/frontend/navigation */ "./core-bundle/assets/scripts/frontend/navigation.js");
/* harmony import */ var _styles_frontend_navigation_pcss__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./styles/frontend/navigation.pcss */ "./core-bundle/assets/styles/frontend/navigation.pcss");


window.AccessibilityNavigation = _scripts_frontend_navigation__WEBPACK_IMPORTED_MODULE_0__.Navigation;
}();
/******/ })()
;
//# sourceMappingURL=data:application/json;charset=utf-8;base64,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d2VicGFjazovLy8uL2NvcmUtYnVuZGxlL2Fzc2V0cy9zY3JpcHRzL2Zyb250ZW5kL25hdmlnYXRpb24uanMiLCJ3ZWJwYWNrOi8vLy4vY29yZS1idW5kbGUvYXNzZXRzL3N0eWxlcy9mcm9udGVuZC9uYXZpZ2F0aW9uLnBjc3M/NmE0YSIsIndlYnBhY2s6Ly8vd2VicGFjay9ib290c3RyYXAiLCJ3ZWJwYWNrOi8vL3dlYnBhY2svcnVudGltZS9kZWZpbmUgcHJvcGVydHkgZ2V0dGVycyIsIndlYnBhY2s6Ly8vd2VicGFjay9ydW50aW1lL2hhc093blByb3BlcnR5IHNob3J0aGFuZCIsIndlYnBhY2s6Ly8vd2VicGFjay9ydW50aW1lL21ha2UgbmFtZXNwYWNlIG9iamVjdCIsIndlYnBhY2s6Ly8vLi9jb3JlLWJ1bmRsZS9hc3NldHMvbmF2aWdhdGlvbi5qcyJdLCJzb3VyY2VzQ29udGVudCI6WyJleHBvcnQgY2xhc3MgTmF2aWdhdGlvbiB7XG4gICAgY29uc3RydWN0b3Iob3B0aW9ucykge1xuICAgICAgICB0aGlzLm9wdGlvbnMgPSB0aGlzLl9tZXJnZSh7XG4gICAgICAgICAgICBzZWxlY3RvcjogJy5uYXZpZ2F0aW9uLW1haW4nLFxuICAgICAgICAgICAgdG9nZ2xlOiAnLm5hdi1idXJnZXInLFxuICAgICAgICAgICAgbWluV2lkdGg6IDEwMjQsXG4gICAgICAgICAgICBjbGFzc2VzOiB7XG4gICAgICAgICAgICAgICAgc3VibWVudUJ1dHRvbjogJ2J0bi10b2dnbGUtc3VibWVudScsXG4gICAgICAgICAgICAgICAgZXhwYW5kOiAnbmF2LWV4cGFuZGVkJyxcbiAgICAgICAgICAgICAgICBhY3RpdmU6ICdpcy1hY3RpdmUnLFxuICAgICAgICAgICAgICAgIGJvZHlPcGVuOiAnbmF2aWdhdGlvbi1vcGVuJyxcbiAgICAgICAgICAgICAgICBib3VuZHNSaWdodDogJ2JvdW5kcy1yaWdodCcsXG4gICAgICAgICAgICAgICAgYm91bmRzTGVmdDogJ2JvdW5kcy1sZWZ0JyxcbiAgICAgICAgICAgIH0sXG4gICAgICAgICAgICBhcmlhTGFiZWxzOiB7XG4gICAgICAgICAgICAgICAgJ2V4cGFuZCc6ICdFeHBhbmQgbWVudTogJyxcbiAgICAgICAgICAgICAgICAnY29sbGFwc2UnOiAnQ29sbGFwc2UgbWVudTogJyxcbiAgICAgICAgICAgIH1cbiAgICAgICAgfSwgb3B0aW9ucyB8fCB7fSk7XG5cbiAgICAgICAgdGhpcy5uYXZpZ2F0aW9uID0gZG9jdW1lbnQucXVlcnlTZWxlY3Rvcih0aGlzLm9wdGlvbnMuc2VsZWN0b3IpO1xuICAgICAgICB0aGlzLnRvZ2dsZSA9IGRvY3VtZW50LnF1ZXJ5U2VsZWN0b3IodGhpcy5vcHRpb25zLnRvZ2dsZSk7XG5cbiAgICAgICAgaWYgKCF0aGlzLm5hdmlnYXRpb24pIHtcbiAgICAgICAgICAgIHJldHVybjtcbiAgICAgICAgfVxuXG4gICAgICAgIHRoaXMuc3RhdGUgPSBmYWxzZTtcblxuICAgICAgICB0aGlzLmRyb3Bkb3ducyA9IFtdO1xuICAgICAgICB0aGlzLmFjdGl2ZSA9IFtdO1xuICAgICAgICB0aGlzLmxpc3RlbmVycyA9IG5ldyBXZWFrTWFwKCk7XG5cbiAgICAgICAgdGhpcy5faW5pdCgpO1xuXG4gICAgICAgIGZvciAoY29uc3QgZHJvcGRvd24gb2YgdGhpcy5kcm9wZG93bnMpIHtcbiAgICAgICAgICAgIHRoaXMuX2FkZFN1Yk1lbnVCdXR0b24oZHJvcGRvd24pO1xuICAgICAgICB9XG5cbiAgICAgICAgbmV3IFJlc2l6ZU9ic2VydmVyKCgpID0+IHtcbiAgICAgICAgICAgIGZvciAoY29uc3QgZHJvcGRvd24gb2YgdGhpcy5kcm9wZG93bnMpIHtcbiAgICAgICAgICAgICAgICB0aGlzLl9pc0Rlc2t0b3AoKSA/IHRoaXMuX3JlZ2lzdGVyRHJvcGRvd25FdmVudHMoZHJvcGRvd24pIDogdGhpcy5fdW5yZWdpc3RlckRyb3Bkb3duRXZlbnRzKGRyb3Bkb3duKTtcbiAgICAgICAgICAgIH1cbiAgICAgICAgfSkub2JzZXJ2ZShkb2N1bWVudC5ib2R5KTtcbiAgICB9XG5cbiAgICAvKipcbiAgICAgKiBNZXJnZXMgY29uZmlndXJhdGlvbiBvcHRpb25zIGFuZCByZXBsYWNlcyB0aGVtIGlmIHRoZXkgZXhpc3RcbiAgICAgKlxuICAgICAqIEBwcml2YXRlXG4gICAgICovXG4gICAgX21lcmdlKGEsIGIpIHtcbiAgICAgICAgcmV0dXJuIFsuLi5uZXcgU2V0KFsuLi5PYmplY3Qua2V5cyhhKSwgLi4uT2JqZWN0LmtleXMoYildKV0ucmVkdWNlKChyZXN1bHQsIGtleSkgPT4gKHtcbiAgICAgICAgICAgIC4uLnJlc3VsdCxcbiAgICAgICAgICAgIFtrZXldOiBcIm9iamVjdFwiID09PSB0eXBlb2YgKGFba2V5XSkgPyBPYmplY3QuYXNzaWduKHt9LCBhW2tleV0sIGJba2V5XSkgOiAhYltrZXldID8gYVtrZXldIDogYltrZXldLFxuICAgICAgICB9KSwge30pO1xuICAgIH1cblxuICAgIC8qKlxuICAgICAqIFBsYWNlaG9sZGVyIGJ1dHRvbiB0aGF0IGlzIGNsb25lZCBmb3IgZWFjaCBzdWJtZW51IGl0ZW1cbiAgICAgKlxuICAgICAqIEBwcml2YXRlXG4gICAgICovXG4gICAgX2NyZWF0ZVN1Yk1lbnVCdXR0b24oKSB7XG4gICAgICAgIHRoaXMuYnRuID0gZG9jdW1lbnQuY3JlYXRlRWxlbWVudCgnYnV0dG9uJyk7XG4gICAgICAgIHRoaXMuYnRuLmNsYXNzTGlzdC5hZGQodGhpcy5vcHRpb25zLmNsYXNzZXMuc3VibWVudUJ1dHRvbik7XG4gICAgICAgIHRoaXMuYnRuLmFyaWFIYXNQb3B1cCA9ICd0cnVlJztcbiAgICAgICAgdGhpcy5idG4uYXJpYUV4cGFuZGVkID0gJ2ZhbHNlJztcbiAgICB9XG5cbiAgICAvKipcbiAgICAgKiBEZXRlcm1pbmVzIHRoZSBmb2N1cyB0cmFwIHRhcmdldHNcbiAgICAgKlxuICAgICAqIEBwcml2YXRlXG4gICAgICovXG4gICAgX2luaXRGb2N1c1RyYXBUYXJnZXRzKCkge1xuICAgICAgICBjb25zdCBub2RlcyA9IFt0aGlzLm5hdmlnYXRpb24uY2xvc2VzdCgnI2hlYWRlcicpPy5xdWVyeVNlbGVjdG9yKCdhW2hyZWZdLmxvZ28nKSwgLi4udGhpcy5uYXZpZ2F0aW9uLnF1ZXJ5U2VsZWN0b3JBbGwoJ2FbaHJlZl06bm90KFtkaXNhYmxlZF0pLCBidXR0b246bm90KFtkaXNhYmxlZF0pJyldO1xuXG4gICAgICAgIHRoaXMuZmlyc3RGb2N1cyA9IG5vZGVzWzBdID8/IHRoaXMudG9nZ2xlO1xuICAgICAgICB0aGlzLmxhc3RGb2N1cyA9IG5vZGVzW25vZGVzLmxlbmd0aCAtIDFdID8/IFtdO1xuICAgIH1cblxuICAgIC8qKlxuICAgICAqIEhhbmRsZXMgdGhlIGZvY3VzIHRyYXAgb24gdGhlIG9wZW4gbW9iaWxlIG5hdmlnYXRpb25cbiAgICAgKlxuICAgICAqIEBwcml2YXRlXG4gICAgICovXG4gICAgX2ZvY3VzVHJhcEV2ZW50KGV2ZW50KSB7XG4gICAgICAgIGlmICghKGV2ZW50LmtleSA9PT0gJ1RhYicgfHwgZXZlbnQua2V5Q29kZSA9PT0gOSkpIHtcbiAgICAgICAgICAgIHJldHVybjtcbiAgICAgICAgfVxuXG4gICAgICAgIGlmIChkb2N1bWVudC5hY3RpdmVFbGVtZW50ID09PSB0aGlzLmxhc3RGb2N1cyAmJiAhZXZlbnQuc2hpZnRLZXkpIHtcbiAgICAgICAgICAgIGV2ZW50LnByZXZlbnREZWZhdWx0KCk7XG4gICAgICAgICAgICB0aGlzLmZpcnN0Rm9jdXM/LmZvY3VzKCk7XG4gICAgICAgIH1cblxuICAgICAgICBpZiAoZG9jdW1lbnQuYWN0aXZlRWxlbWVudCA9PT0gdGhpcy5maXJzdEZvY3VzICYmIGV2ZW50LnNoaWZ0S2V5KSB7XG4gICAgICAgICAgICBldmVudC5wcmV2ZW50RGVmYXVsdCgpO1xuICAgICAgICAgICAgdGhpcy5sYXN0Rm9jdXM/LmZvY3VzKCk7XG4gICAgICAgIH1cbiAgICB9XG5cbiAgICAvKipcbiAgICAgKiBUb2dnbGVzIHRoZSBtZW51IHN0YXRlIG9uIG1vYmlsZVxuICAgICAqXG4gICAgICogQHByaXZhdGVcbiAgICAgKi9cbiAgICBfdG9nZ2xlTWVudVN0YXRlKCkge1xuICAgICAgICB0aGlzLnRvZ2dsZS5hcmlhRXhwYW5kZWQgPSB0aGlzLnN0YXRlID8gJ2ZhbHNlJyA6ICd0cnVlJztcbiAgICAgICAgdGhpcy50b2dnbGUuY2xhc3NMaXN0LnRvZ2dsZSh0aGlzLm9wdGlvbnMuY2xhc3Nlcy5hY3RpdmUsICF0aGlzLnN0YXRlKTtcbiAgICAgICAgdGhpcy5uYXZpZ2F0aW9uLmNsYXNzTGlzdC50b2dnbGUodGhpcy5vcHRpb25zLmNsYXNzZXMuYWN0aXZlLCAhdGhpcy5zdGF0ZSk7XG5cbiAgICAgICAgZG9jdW1lbnQuYm9keS5jbGFzc0xpc3QudG9nZ2xlKHRoaXMub3B0aW9ucy5jbGFzc2VzLmJvZHlPcGVuLCAhdGhpcy5zdGF0ZSk7XG5cbiAgICAgICAgdGhpcy5zdGF0ZSA9ICF0aGlzLnN0YXRlO1xuICAgIH1cblxuICAgIC8qKlxuICAgICAqIEFkZHMgYW5kIHJlbW92ZXMgdGhlIGZvY3VzVHJhcCBiYXNlZCBvbiB0aGUgbW9iaWxlIG5hdmlnYXRpb24gc3RhdGVcbiAgICAgKlxuICAgICAqIEBwcml2YXRlXG4gICAgICovXG4gICAgX2ZvY3VzTWVudSgpIHtcbiAgICAgICAgaWYgKHRoaXMuc3RhdGUpIHtcbiAgICAgICAgICAgIGRvY3VtZW50LmFkZEV2ZW50TGlzdGVuZXIoJ2tleWRvd24nLCB0aGlzLl9mb2N1c1RyYXBFdmVudCwgZmFsc2UpO1xuICAgICAgICB9IGVsc2Uge1xuICAgICAgICAgICAgZG9jdW1lbnQucmVtb3ZlRXZlbnRMaXN0ZW5lcigna2V5ZG93bicsIHRoaXMuX2ZvY3VzVHJhcEV2ZW50LCBmYWxzZSk7XG4gICAgICAgIH1cbiAgICB9XG5cbiAgICAvKipcbiAgICAgKiBJbml0aWFsaXplcyBuYXZpZ2F0aW9uIGl0ZW1zIGFuZCBzZXRzIGFyaWEtYXR0cmlidXRlcyBpZiB0aGV5IGRvIG5vdCBleGlzdFxuICAgICAqXG4gICAgICogQHByaXZhdGVcbiAgICAgKi9cbiAgICBfaW5pdCgpIHtcbiAgICAgICAgdGhpcy5fY3JlYXRlU3ViTWVudUJ1dHRvbigpO1xuICAgICAgICB0aGlzLl9pbml0TW9iaWxlVG9nZ2xlRXZlbnRzKCk7XG5cbiAgICAgICAgdGhpcy5uYXZpZ2F0aW9uLnF1ZXJ5U2VsZWN0b3JBbGwoJ2xpJykuZm9yRWFjaChpdGVtID0+IHtcblxuICAgICAgICAgICAgaWYgKGl0ZW0uY2xhc3NMaXN0LmNvbnRhaW5zKCdzdWJtZW51JykpIHtcbiAgICAgICAgICAgICAgICB0aGlzLmRyb3Bkb3ducy5wdXNoKGl0ZW0pO1xuICAgICAgICAgICAgfVxuXG4gICAgICAgICAgICBjb25zdCBuYXZJdGVtID0gaXRlbS5maXJzdEVsZW1lbnRDaGlsZDtcblxuICAgICAgICAgICAgaWYgKG5hdkl0ZW0uY2xhc3NMaXN0LmNvbnRhaW5zKCdhY3RpdmUnKSkge1xuICAgICAgICAgICAgICAgIG5hdkl0ZW0uYXJpYUN1cnJlbnQgPSAncGFnZSc7XG4gICAgICAgICAgICB9XG5cbiAgICAgICAgICAgIGlmICghbmF2SXRlbS5hcmlhTGFiZWwgJiYgbmF2SXRlbS50aXRsZSkge1xuICAgICAgICAgICAgICAgIG5hdkl0ZW0uYXJpYUxhYmVsID0gbmF2SXRlbS50aXRsZTtcbiAgICAgICAgICAgICAgICBuYXZJdGVtLnJlbW92ZUF0dHJpYnV0ZSgndGl0bGUnKTtcbiAgICAgICAgICAgIH1cbiAgICAgICAgfSk7XG5cbiAgICAgICAgLy8gSGlkZSB0aGUgYWN0aXZlIG5hdmlnYXRpb24gb24gZXNjYXBlXG4gICAgICAgIGRvY3VtZW50LmFkZEV2ZW50TGlzdGVuZXIoJ2tleXVwJywgKGUpID0+IHtcbiAgICAgICAgICAgIGUua2V5ID09PSAnRXNjYXBlJyAmJiB0aGlzLl9oaWRlRHJvcGRvd24oKTtcbiAgICAgICAgfSk7XG4gICAgfVxuXG4gICAgLyoqXG4gICAgICogVXBkYXRlcyB0aGUgYXJpYSBsYWJlbHMgYW5kIHN0YXRlIGZvciB0aGUgZHJvcGRvd24gYnV0dG9uc1xuICAgICAqXG4gICAgICogQHByaXZhdGVcbiAgICAgKi9cbiAgICBfdXBkYXRlQXJpYVN0YXRlKGRyb3Bkb3duLCBzaG93KSB7XG4gICAgICAgIGRyb3Bkb3duLmJ0bi5hcmlhTGFiZWwgPSAoc2hvdyA/IHRoaXMub3B0aW9ucy5hcmlhTGFiZWxzLmNvbGxhcHNlIDogdGhpcy5vcHRpb25zLmFyaWFMYWJlbHMuZXhwYW5kKSArIGRyb3Bkb3duLmJ0bi5kYXRhc2V0LmxhYmVsO1xuICAgICAgICBkcm9wZG93bi5idG4uYXJpYUV4cGFuZGVkID0gc2hvdyA/ICd0cnVlJyA6ICdmYWxzZSc7XG4gICAgfVxuXG4gICAgLyoqXG4gICAgICogQ29sbGFwc2VzIHRoZSBkcm9wZG93blxuICAgICAqXG4gICAgICogQHByaXZhdGVcbiAgICAgKi9cbiAgICBfY29sbGFwc2VTdWJtZW51KGRyb3Bkb3duKSB7XG4gICAgICAgIGRyb3Bkb3duLmNsYXNzTGlzdC5yZW1vdmUodGhpcy5vcHRpb25zLmNsYXNzZXMuZXhwYW5kKTtcblxuICAgICAgICBkcm9wZG93bi5xdWVyeVNlbGVjdG9yKCc6c2NvcGUgPiB1bCcpPy5jbGFzc0xpc3QucmVtb3ZlKFxuICAgICAgICAgICAgdGhpcy5vcHRpb25zLmNsYXNzZXMuYm91bmRzTGVmdCxcbiAgICAgICAgICAgIHRoaXMub3B0aW9ucy5jbGFzc2VzLmJvdW5kc1JpZ2h0LFxuICAgICAgICApO1xuXG4gICAgICAgIHRoaXMuX3VwZGF0ZUFyaWFTdGF0ZShkcm9wZG93biwgZmFsc2UpO1xuICAgIH1cblxuICAgIC8qKlxuICAgICAqIEhhbmRsZXMgaGlkaW5nIGRyb3Bkb3ducy4gQWRkaW5nIG5vIHBhcmFtZXRlciB3aWxsIGNsb3NlIGFsbFxuICAgICAqXG4gICAgICogQHByaXZhdGVcbiAgICAgKi9cbiAgICBfaGlkZURyb3Bkb3duKGRyb3Bkb3duID0gbnVsbCkge1xuICAgICAgICBpZiAoMCA9PT0gdGhpcy5hY3RpdmUubGVuZ3RoKSB7XG4gICAgICAgICAgICByZXR1cm47XG4gICAgICAgIH1cblxuICAgICAgICAvLyBDYXNlIDE6IExlYXZpbmcgdGhlIHByZXZpb3VzIGRyb3Bkb3duIChlLmcuIGZvY3VzIGxlZnQpXG4gICAgICAgIGlmICh0aGlzLmFjdGl2ZS5pbmNsdWRlcyhkcm9wZG93bikpIHtcbiAgICAgICAgICAgIHRoaXMuX2NvbGxhcHNlU3VibWVudShkcm9wZG93bik7XG4gICAgICAgICAgICB0aGlzLmFjdGl2ZSA9IHRoaXMuYWN0aXZlLmZpbHRlcihub2RlID0+IG5vZGUgIT09IGRyb3Bkb3duKTtcbiAgICAgICAgfVxuXG4gICAgICAgIC8vIENhc2UgMjogTm90IGNvbnRhaW5lZCBpbiB0aGUgdHJlZSBhdCBhbGwsIHJlbW92ZSBldmVyeXRoaW5nXG4gICAgICAgIGVsc2UgaWYgKG51bGwgPT09IGRyb3Bkb3duIHx8IHRoaXMuYWN0aXZlWzBdICE9PSBkcm9wZG93biAmJiAhdGhpcy5hY3RpdmVbMF0uY29udGFpbnMoZHJvcGRvd24pKSB7XG4gICAgICAgICAgICB0aGlzLmFjdGl2ZS5mb3JFYWNoKG5vZGUgPT4gdGhpcy5fY29sbGFwc2VTdWJtZW51KG5vZGUpKTtcbiAgICAgICAgICAgIHRoaXMuYWN0aXZlID0gW107XG4gICAgICAgIH1cblxuICAgICAgICAvLyBDYXNlIDM6IERvd24gdGhlIGRyYWluIHdpdGggZXZlcnl0aGluZyB0aGF0IGFpbid0IGEgcGFyZW50IG5vZGUgOilcbiAgICAgICAgZWxzZSB7XG4gICAgICAgICAgICB0aGlzLmFjdGl2ZSA9IHRoaXMuYWN0aXZlLmZpbHRlcihub2RlID0+IHtcbiAgICAgICAgICAgICAgICBpZiAobm9kZS5jb250YWlucyhkcm9wZG93bikpIHtcbiAgICAgICAgICAgICAgICAgICAgcmV0dXJuIHRydWU7XG4gICAgICAgICAgICAgICAgfVxuXG4gICAgICAgICAgICAgICAgdGhpcy5fY29sbGFwc2VTdWJtZW51KG5vZGUpO1xuICAgICAgICAgICAgICAgIHJldHVybiBmYWxzZTtcbiAgICAgICAgICAgIH0pO1xuICAgICAgICB9XG4gICAgfVxuXG4gICAgX3NldERyb3Bkb3duUG9zaXRpb24oZHJvcGRvd24pIHtcbiAgICAgICAgY29uc3Qgc3VibWVudSA9IGRyb3Bkb3duLnF1ZXJ5U2VsZWN0b3IoJzpzY29wZSA+IHVsJyk7XG5cbiAgICAgICAgaWYgKG51bGwgPT09IHN1Ym1lbnUpIHtcbiAgICAgICAgICAgIHJldHVybjtcbiAgICAgICAgfVxuXG4gICAgICAgIGlmIChzdWJtZW51LmdldEJvdW5kaW5nQ2xpZW50UmVjdCgpLnJpZ2h0ID49IHdpbmRvdy5pbm5lcldpZHRoKSB7XG4gICAgICAgICAgICBzdWJtZW51LmNsYXNzTGlzdC5hZGQodGhpcy5vcHRpb25zLmNsYXNzZXMuYm91bmRzUmlnaHQpO1xuICAgICAgICB9IGVsc2UgaWYgKHN1Ym1lbnUuZ2V0Qm91bmRpbmdDbGllbnRSZWN0KCkubGVmdCA8IDApIHtcbiAgICAgICAgICAgIHN1Ym1lbnUuY2xhc3NMaXN0LmFkZCh0aGlzLm9wdGlvbnMuY2xhc3Nlcy5ib3VuZHNMZWZ0KTtcbiAgICAgICAgfVxuICAgIH1cblxuICAgIC8qKlxuICAgICAqIFNob3dzIHRoZSBkcm9wZG93blxuICAgICAqXG4gICAgICogQHByaXZhdGVcbiAgICAgKi9cbiAgICBfc2hvd0Ryb3Bkb3duKGRyb3Bkb3duKSB7XG4gICAgICAgIHRoaXMuX2hpZGVEcm9wZG93bihkcm9wZG93bik7XG5cbiAgICAgICAgZHJvcGRvd24uY2xhc3NMaXN0LmFkZCh0aGlzLm9wdGlvbnMuY2xhc3Nlcy5leHBhbmQpO1xuXG4gICAgICAgIGlmICh0aGlzLl9pc0Rlc2t0b3AoKSkge1xuICAgICAgICAgICAgdGhpcy5fc2V0RHJvcGRvd25Qb3NpdGlvbihkcm9wZG93bik7XG4gICAgICAgIH1cblxuICAgICAgICB0aGlzLl91cGRhdGVBcmlhU3RhdGUoZHJvcGRvd24sIHRydWUpO1xuXG4gICAgICAgIGlmICghdGhpcy5hY3RpdmUuaW5jbHVkZXMoZHJvcGRvd24pKSB7XG4gICAgICAgICAgICB0aGlzLmFjdGl2ZS5wdXNoKGRyb3Bkb3duKTtcbiAgICAgICAgfVxuICAgIH1cblxuICAgIC8qKlxuICAgICAqIFVwZGF0ZXMgdGhlIGRyb3Bkb3duIHN0YXRlXG4gICAgICpcbiAgICAgKiBAcHJpdmF0ZVxuICAgICAqL1xuICAgIF90b2dnbGVEcm9wZG93blN0YXRlKGRyb3Bkb3duLCBzaG93KSB7XG4gICAgICAgIHNob3cgPyB0aGlzLl9zaG93RHJvcGRvd24oZHJvcGRvd24pIDogdGhpcy5faGlkZURyb3Bkb3duKGRyb3Bkb3duKTtcbiAgICB9XG5cbiAgICAvKipcbiAgICAgKiBBZGRzIGEgc3VibWVudSBidXR0b24gdGhhdCB0b2dnbGVzIHN1Ym1lbnUgbmF2aWdhdGlvbnNcbiAgICAgKlxuICAgICAqIEBwcml2YXRlXG4gICAgICovXG4gICAgX2FkZFN1Yk1lbnVCdXR0b24oZHJvcGRvd24pIHtcbiAgICAgICAgY29uc3QgaXRlbSA9IGRyb3Bkb3duLmZpcnN0RWxlbWVudENoaWxkLFxuICAgICAgICAgICAgICBidG4gPSB0aGlzLmJ0bi5jbG9uZU5vZGUoKTtcblxuICAgICAgICBkcm9wZG93bi5idG4gPSBidG47XG5cbiAgICAgICAgYnRuLmRhdGFzZXQubGFiZWwgPSBpdGVtLnRleHRDb250ZW50O1xuICAgICAgICBidG4uYXJpYUxhYmVsID0gdGhpcy5vcHRpb25zLmFyaWFMYWJlbHMuZXhwYW5kICsgaXRlbS50ZXh0Q29udGVudDtcblxuICAgICAgICBidG4uYWRkRXZlbnRMaXN0ZW5lcignY2xpY2snLCAoKSA9PiB7XG4gICAgICAgICAgICBjb25zdCBzaG93ID0gYnRuLmFyaWFFeHBhbmRlZCA9PT0gJ2ZhbHNlJyA/PyB0cnVlO1xuICAgICAgICAgICAgdGhpcy5fdG9nZ2xlRHJvcGRvd25TdGF0ZShkcm9wZG93biwgc2hvdyk7XG4gICAgICAgIH0pO1xuXG4gICAgICAgIGl0ZW0uYWZ0ZXIoYnRuKTtcbiAgICB9XG5cbiAgICAvKipcbiAgICAgKiBNb3VzZSBlbnRlciBldmVudCBmb3IgZHJvcGRvd25zXG4gICAgICpcbiAgICAgKiBAcHJpdmF0ZVxuICAgICAqL1xuICAgIF9tb3VzZUVudGVyKGUsIGRyb3Bkb3duKSB7XG4gICAgICAgIHRoaXMuX3RvZ2dsZURyb3Bkb3duU3RhdGUoZHJvcGRvd24sIHRydWUpO1xuICAgIH1cblxuICAgIC8qKlxuICAgICAqIE1vdXNlIGxlYXZlIGV2ZW50IGZvciBkcm9wZG93bnNcbiAgICAgKlxuICAgICAqIEBwcml2YXRlXG4gICAgICovXG4gICAgX21vdXNlTGVhdmUoZSwgZHJvcGRvd24pIHtcbiAgICAgICAgdGhpcy5faGlkZURyb3Bkb3duKGRyb3Bkb3duKTtcbiAgICB9XG5cbiAgICAvKipcbiAgICAgKiBMaXN0ZW5lciBmb3IgdGhlIGZvY3Vzb3V0IGV2ZW50IHdoZW4gYW4gZWxlbWVudCBsb3NlcyBpdCdzIGZvY3VzLCBuZWNlc3NhcnkgZm9yIHRhYiBjb250cm9sXG4gICAgICpcbiAgICAgKiBAcHJpdmF0ZVxuICAgICAqL1xuICAgIF9mb2N1c091dChlLCBkcm9wZG93bikge1xuICAgICAgICBpZiAoZS5yZWxhdGVkVGFyZ2V0ICYmIHRoaXMuYWN0aXZlLmxlbmd0aCA+IDAgJiYgIWRyb3Bkb3duLmNvbnRhaW5zKGUucmVsYXRlZFRhcmdldCkpIHtcbiAgICAgICAgICAgIHRoaXMuX2hpZGVEcm9wZG93bihkcm9wZG93bik7XG4gICAgICAgIH1cbiAgICB9XG5cbiAgICBfaXNEZXNrdG9wKCkge1xuICAgICAgICByZXR1cm4gd2luZG93LmlubmVyV2lkdGggPj0gdGhpcy5vcHRpb25zLm1pbldpZHRoO1xuICAgIH1cblxuICAgIF9pbml0TW9iaWxlVG9nZ2xlRXZlbnRzKCkge1xuICAgICAgICB0aGlzLl9pbml0Rm9jdXNUcmFwVGFyZ2V0cygpO1xuICAgICAgICB0aGlzLl9mb2N1c1RyYXBFdmVudCA9IHRoaXMuX2ZvY3VzVHJhcEV2ZW50LmJpbmQodGhpcyk7XG5cbiAgICAgICAgdGhpcy50b2dnbGU/LmFkZEV2ZW50TGlzdGVuZXIoJ2NsaWNrJywgKCkgPT4ge1xuICAgICAgICAgICAgaWYgKHRoaXMuX2lzRGVza3RvcCgpKSB7XG4gICAgICAgICAgICAgICAgcmV0dXJuO1xuICAgICAgICAgICAgfVxuXG4gICAgICAgICAgICB0aGlzLl90b2dnbGVNZW51U3RhdGUoKTtcbiAgICAgICAgICAgIHRoaXMuX2ZvY3VzTWVudSgpO1xuICAgICAgICB9KTtcbiAgICB9XG5cbiAgICAvKipcbiAgICAgKiBSZWdpc3RlcnMgdGhlIG1vdXNlIGRyb3Bkb3duIGV2ZW50c1xuICAgICAqXG4gICAgICogQHByaXZhdGVcbiAgICAgKi9cbiAgICBfcmVnaXN0ZXJEcm9wZG93bkV2ZW50cyhkcm9wZG93bikge1xuICAgICAgICBpZiAodGhpcy5saXN0ZW5lcnMuaGFzKGRyb3Bkb3duKSkge1xuICAgICAgICAgICAgcmV0dXJuO1xuICAgICAgICB9XG5cbiAgICAgICAgY29uc3QgZXZlbnRzID0ge1xuICAgICAgICAgICAgbW91c2VlbnRlcjogKGUpID0+IHRoaXMuX21vdXNlRW50ZXIoZSwgZHJvcGRvd24pLFxuICAgICAgICAgICAgbW91c2VsZWF2ZTogKGUpID0+IHRoaXMuX21vdXNlTGVhdmUoZSwgZHJvcGRvd24pLFxuICAgICAgICAgICAgZm9jdXNvdXQ6IChlKSA9PiB0aGlzLl9mb2N1c091dChlLCBkcm9wZG93biksXG4gICAgICAgIH1cblxuICAgICAgICBmb3IoY29uc3QgW3R5cGUsIGV2ZW50XSBvZiBPYmplY3QuZW50cmllcyhldmVudHMpKSB7XG4gICAgICAgICAgICBkcm9wZG93bi5hZGRFdmVudExpc3RlbmVyKHR5cGUsIGV2ZW50KTtcbiAgICAgICAgfVxuXG4gICAgICAgIHRoaXMubGlzdGVuZXJzLnNldChkcm9wZG93biwgZXZlbnRzKTtcbiAgICB9XG5cbiAgICAvKipcbiAgICAgKiBSZW1vdmVzIHRoZSBtb3VzZSBkcm9wZG93biBldmVudHNcbiAgICAgKlxuICAgICAqIEBwcml2YXRlXG4gICAgICovXG4gICAgX3VucmVnaXN0ZXJEcm9wZG93bkV2ZW50cyhkcm9wZG93bikge1xuICAgICAgICBjb25zdCBldmVudHMgPSB0aGlzLmxpc3RlbmVycy5nZXQoZHJvcGRvd24pO1xuXG4gICAgICAgIGlmICghZXZlbnRzKSB7XG4gICAgICAgICAgICByZXR1cm47XG4gICAgICAgIH1cblxuICAgICAgICBmb3IgKGNvbnN0IFt0eXBlLCBldmVudF0gb2YgT2JqZWN0LmVudHJpZXMoZXZlbnRzKSkge1xuICAgICAgICAgICAgZHJvcGRvd24ucmVtb3ZlRXZlbnRMaXN0ZW5lcih0eXBlLCBldmVudCk7XG4gICAgICAgIH1cblxuICAgICAgICB0aGlzLmxpc3RlbmVycy5kZWxldGUoZHJvcGRvd24pO1xuICAgIH1cbn1cbiIsIi8vIGV4dHJhY3RlZCBieSBtaW5pLWNzcy1leHRyYWN0LXBsdWdpblxuZXhwb3J0IHt9OyIsIi8vIFRoZSBtb2R1bGUgY2FjaGVcbnZhciBfX3dlYnBhY2tfbW9kdWxlX2NhY2hlX18gPSB7fTtcblxuLy8gVGhlIHJlcXVpcmUgZnVuY3Rpb25cbmZ1bmN0aW9uIF9fd2VicGFja19yZXF1aXJlX18obW9kdWxlSWQpIHtcblx0Ly8gQ2hlY2sgaWYgbW9kdWxlIGlzIGluIGNhY2hlXG5cdHZhciBjYWNoZWRNb2R1bGUgPSBfX3dlYnBhY2tfbW9kdWxlX2NhY2hlX19bbW9kdWxlSWRdO1xuXHRpZiAoY2FjaGVkTW9kdWxlICE9PSB1bmRlZmluZWQpIHtcblx0XHRyZXR1cm4gY2FjaGVkTW9kdWxlLmV4cG9ydHM7XG5cdH1cblx0Ly8gQ3JlYXRlIGEgbmV3IG1vZHVsZSAoYW5kIHB1dCBpdCBpbnRvIHRoZSBjYWNoZSlcblx0dmFyIG1vZHVsZSA9IF9fd2VicGFja19tb2R1bGVfY2FjaGVfX1ttb2R1bGVJZF0gPSB7XG5cdFx0Ly8gbm8gbW9kdWxlLmlkIG5lZWRlZFxuXHRcdC8vIG5vIG1vZHVsZS5sb2FkZWQgbmVlZGVkXG5cdFx0ZXhwb3J0czoge31cblx0fTtcblxuXHQvLyBFeGVjdXRlIHRoZSBtb2R1bGUgZnVuY3Rpb25cblx0X193ZWJwYWNrX21vZHVsZXNfX1ttb2R1bGVJZF0obW9kdWxlLCBtb2R1bGUuZXhwb3J0cywgX193ZWJwYWNrX3JlcXVpcmVfXyk7XG5cblx0Ly8gUmV0dXJuIHRoZSBleHBvcnRzIG9mIHRoZSBtb2R1bGVcblx0cmV0dXJuIG1vZHVsZS5leHBvcnRzO1xufVxuXG4iLCIvLyBkZWZpbmUgZ2V0dGVyIGZ1bmN0aW9ucyBmb3IgaGFybW9ueSBleHBvcnRzXG5fX3dlYnBhY2tfcmVxdWlyZV9fLmQgPSBmdW5jdGlvbihleHBvcnRzLCBkZWZpbml0aW9uKSB7XG5cdGZvcih2YXIga2V5IGluIGRlZmluaXRpb24pIHtcblx0XHRpZihfX3dlYnBhY2tfcmVxdWlyZV9fLm8oZGVmaW5pdGlvbiwga2V5KSAmJiAhX193ZWJwYWNrX3JlcXVpcmVfXy5vKGV4cG9ydHMsIGtleSkpIHtcblx0XHRcdE9iamVjdC5kZWZpbmVQcm9wZXJ0eShleHBvcnRzLCBrZXksIHsgZW51bWVyYWJsZTogdHJ1ZSwgZ2V0OiBkZWZpbml0aW9uW2tleV0gfSk7XG5cdFx0fVxuXHR9XG59OyIsIl9fd2VicGFja19yZXF1aXJlX18ubyA9IGZ1bmN0aW9uKG9iaiwgcHJvcCkgeyByZXR1cm4gT2JqZWN0LnByb3RvdHlwZS5oYXNPd25Qcm9wZXJ0eS5jYWxsKG9iaiwgcHJvcCk7IH0iLCIvLyBkZWZpbmUgX19lc01vZHVsZSBvbiBleHBvcnRzXG5fX3dlYnBhY2tfcmVxdWlyZV9fLnIgPSBmdW5jdGlvbihleHBvcnRzKSB7XG5cdGlmKHR5cGVvZiBTeW1ib2wgIT09ICd1bmRlZmluZWQnICYmIFN5bWJvbC50b1N0cmluZ1RhZykge1xuXHRcdE9iamVjdC5kZWZpbmVQcm9wZXJ0eShleHBvcnRzLCBTeW1ib2wudG9TdHJpbmdUYWcsIHsgdmFsdWU6ICdNb2R1bGUnIH0pO1xuXHR9XG5cdE9iamVjdC5kZWZpbmVQcm9wZXJ0eShleHBvcnRzLCAnX19lc01vZHVsZScsIHsgdmFsdWU6IHRydWUgfSk7XG59OyIsImltcG9ydCB7IE5hdmlnYXRpb24gfSBmcm9tICcuL3NjcmlwdHMvZnJvbnRlbmQvbmF2aWdhdGlvbic7XG5cbmltcG9ydCAnLi9zdHlsZXMvZnJvbnRlbmQvbmF2aWdhdGlvbi5wY3NzJztcblxud2luZG93LkFjY2Vzc2liaWxpdHlOYXZpZ2F0aW9uID0gTmF2aWdhdGlvbjtcbiJdLCJuYW1lcyI6WyJOYXZpZ2F0aW9uIiwiY29uc3RydWN0b3IiLCJvcHRpb25zIiwiX21lcmdlIiwic2VsZWN0b3IiLCJ0b2dnbGUiLCJtaW5XaWR0aCIsImNsYXNzZXMiLCJzdWJtZW51QnV0dG9uIiwiZXhwYW5kIiwiYWN0aXZlIiwiYm9keU9wZW4iLCJib3VuZHNSaWdodCIsImJvdW5kc0xlZnQiLCJhcmlhTGFiZWxzIiwibmF2aWdhdGlvbiIsImRvY3VtZW50IiwicXVlcnlTZWxlY3RvciIsInN0YXRlIiwiZHJvcGRvd25zIiwibGlzdGVuZXJzIiwiV2Vha01hcCIsIl9pbml0IiwiZHJvcGRvd24iLCJfYWRkU3ViTWVudUJ1dHRvbiIsIlJlc2l6ZU9ic2VydmVyIiwiX2lzRGVza3RvcCIsIl9yZWdpc3RlckRyb3Bkb3duRXZlbnRzIiwiX3VucmVnaXN0ZXJEcm9wZG93bkV2ZW50cyIsIm9ic2VydmUiLCJib2R5IiwiYSIsImIiLCJTZXQiLCJPYmplY3QiLCJrZXlzIiwicmVkdWNlIiwicmVzdWx0Iiwia2V5IiwiYXNzaWduIiwiX2NyZWF0ZVN1Yk1lbnVCdXR0b24iLCJidG4iLCJjcmVhdGVFbGVtZW50IiwiY2xhc3NMaXN0IiwiYWRkIiwiYXJpYUhhc1BvcHVwIiwiYXJpYUV4cGFuZGVkIiwiX2luaXRGb2N1c1RyYXBUYXJnZXRzIiwibm9kZXMiLCJjbG9zZXN0IiwicXVlcnlTZWxlY3RvckFsbCIsImZpcnN0Rm9jdXMiLCJsYXN0Rm9jdXMiLCJsZW5ndGgiLCJfZm9jdXNUcmFwRXZlbnQiLCJldmVudCIsImtleUNvZGUiLCJhY3RpdmVFbGVtZW50Iiwic2hpZnRLZXkiLCJwcmV2ZW50RGVmYXVsdCIsImZvY3VzIiwiX3RvZ2dsZU1lbnVTdGF0ZSIsIl9mb2N1c01lbnUiLCJhZGRFdmVudExpc3RlbmVyIiwicmVtb3ZlRXZlbnRMaXN0ZW5lciIsIl9pbml0TW9iaWxlVG9nZ2xlRXZlbnRzIiwiZm9yRWFjaCIsIml0ZW0iLCJjb250YWlucyIsInB1c2giLCJuYXZJdGVtIiwiZmlyc3RFbGVtZW50Q2hpbGQiLCJhcmlhQ3VycmVudCIsImFyaWFMYWJlbCIsInRpdGxlIiwicmVtb3ZlQXR0cmlidXRlIiwiZSIsIl9oaWRlRHJvcGRvd24iLCJfdXBkYXRlQXJpYVN0YXRlIiwic2hvdyIsImNvbGxhcHNlIiwiZGF0YXNldCIsImxhYmVsIiwiX2NvbGxhcHNlU3VibWVudSIsInJlbW92ZSIsImluY2x1ZGVzIiwiZmlsdGVyIiwibm9kZSIsIl9zZXREcm9wZG93blBvc2l0aW9uIiwic3VibWVudSIsImdldEJvdW5kaW5nQ2xpZW50UmVjdCIsInJpZ2h0Iiwid2luZG93IiwiaW5uZXJXaWR0aCIsImxlZnQiLCJfc2hvd0Ryb3Bkb3duIiwiX3RvZ2dsZURyb3Bkb3duU3RhdGUiLCJjbG9uZU5vZGUiLCJ0ZXh0Q29udGVudCIsImFmdGVyIiwiX21vdXNlRW50ZXIiLCJfbW91c2VMZWF2ZSIsIl9mb2N1c091dCIsInJlbGF0ZWRUYXJnZXQiLCJiaW5kIiwiaGFzIiwiZXZlbnRzIiwibW91c2VlbnRlciIsIm1vdXNlbGVhdmUiLCJmb2N1c291dCIsInR5cGUiLCJlbnRyaWVzIiwic2V0IiwiZ2V0IiwiZGVsZXRlIiwiQWNjZXNzaWJpbGl0eU5hdmlnYXRpb24iXSwic291cmNlUm9vdCI6IiJ9