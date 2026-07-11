import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    #pendingAutoFocus = null;

    static targets = ['scrollTo', 'autoFocus', 'widgetError'];

    static values = {
        sessionKey: {
            type: String,
            default: 'contao_backend_offset',
        },
        behavior: {
            type: String,
            default: 'instant',
        },
        block: {
            type: String,
            default: 'center',
        },
    };

    initialize() {
        this.store = this.store.bind(this);
    }

    connect() {
        this.restore();
    }

    async restore() {
        // Scrolls to a pending autofocus if one exists and resets the guard
        if (this.#scrollToAutoFocusTarget()) return;

        if (!this.offset) return;

        // Execute scroll restore after Turbo scrolled to top
        await new Promise(requestAnimationFrame);

        window.scrollTo({
            top: this.offset,
            behavior: this.behaviorValue,
            block: this.blockValue,
        });

        this.offset = null;
    }

    scrollToTargetConnected() {
        this.scrollToTarget.scrollIntoView({
            behavior: this.behaviorValue,
            block: this.blockValue,
        });
    }

    autoFocusTargetConnected() {
        if (this.#pendingAutoFocus) return;

        const input = this.autoFocusTarget;

        if (
            input.disabled ||
            input.readonly ||
            !input.offsetWidth ||
            !input.offsetHeight ||
            input.closest('.chzn-search') ||
            (input.autocomplete && input.autocomplete !== 'off')
        ) {
            return;
        }

        // Save the pendingAutoFocus that will be executed in the #restore method on turbo:render
        this.#pendingAutoFocus = input;
    }

    widgetErrorTargetConnected() {
        this.widgetErrorTarget.scrollIntoView({
            behavior: 'smooth',
            block: 'start',
        });
    }

    store() {
        this.offset = this.element.scrollTop;
    }

    discard() {
        this.offset = null;
    }

    scrollToWidgetError() {
        if (this.hasWidgetErrorTarget) {
            this.widgetErrorTargetConnected();
        }
    }

    get offset() {
        const value = window.sessionStorage.getItem(this.sessionKeyValue);

        return value ? Number.parseInt(value, 10) : null;
    }

    set offset(value) {
        if (value === null || value === undefined) {
            window.sessionStorage.removeItem(this.sessionKeyValue);
        } else {
            window.sessionStorage.setItem(this.sessionKeyValue, String(value));
        }
    }

    #scrollToAutoFocusTarget() {
        if (!this.#pendingAutoFocus) {
            return false;
        }

        const input = this.#pendingAutoFocus;

        queueMicrotask(() => {
            // Do prevent scroll to allow turbo to handle it (see #8934)
            input.focus({ preventScroll: true });
            const len = input.value.length;
            input.setSelectionRange(len, len);
        });

        this.#pendingAutoFocus = null;

        return true;
    }
}
