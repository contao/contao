export class FocusTrap {
    constructor(element) {
        this.element = element;
        this._enabled = false;
    }

    /**
     * Enable the focus trap: Start listening for keydown events and prevent
     * tabbing out of the element (wrap around). If a condition callback is
     * defined, it will be invoked on any tab action. It can be used to
     * dynamically enable/disable the focus trap.
     *
     * @param conditionCallback Return false if the focus trap should currently not apply
     */
    enable(conditionCallback = null) {
        this.conditionCallback = conditionCallback;

        this._enabled = true;
        document.addEventListener('keydown', this._keydown.bind(this));
    }

    /**
     * Disable the focus trap.
     */
    disable() {
        this._enabled = false;
        document.removeEventListener('keydown', this._keydown);
    }

    /**
     * Returns true if the focus trap is currently enabled. If a condition
     * callback was set, it will get evaluated as well.
     */
    get enabled() {
        return this._enabled && this._testCondition();
    }

    _keydown(event) {
        if (event.keyCode !== 9 || !this._testCondition()) {
            return;
        }

        const elements = this._getKeyboardFocusableElements();

        if (event.shiftKey) {
            // tab back
            if (elements.indexOf(document.activeElement) === 0) {
                event.preventDefault();
                elements[elements.length - 1].focus();
            }

            return;
        }

        // tab forward
        if (elements.indexOf(document.activeElement) === elements.length - 1) {
            event.preventDefault();
            elements[0].focus();
        }
    }

    _testCondition() {
        return null === this.conditionCallback || this.conditionCallback() !== false;
    }

    _getKeyboardFocusableElements() {
        const selectors = 'a[href], area[href], input:not([disabled]), select:not([disabled]), textarea:not([disabled]), button:not([disabled]), iframe, object, embed, *[tabindex], *[contenteditable]';

        return Array
            .from(this.element.querySelectorAll(selectors))
            .filter(el => el.tabIndex > -1 && window.getComputedStyle(el).display !== 'none')
            ;
    }
}
