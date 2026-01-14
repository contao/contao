import { Controller } from '@hotwired/stimulus';
import * as focusTrap from 'focus-trap';
import { TurboStreamConnection } from '../modules/turbo-stream-connection';

export default class BackendSearchController extends Controller {
    static targets = ['input', 'results', 'shortcut'];

    static values = {
        url: String,
        minCharacters: Number,
        debounceDelay: Number,
        shortcutLabel: String,
        shortcutMacosLabel: String,
    };

    static classes = ['hidden', 'initial', 'loading', 'invalid', 'results', 'error'];

    initialize() {
        this.shortcutTarget.innerText = /(Mac|iPhone|iPad)/.test(navigator.platform)
            ? this.shortcutMacosLabelValue
            : this.shortcutLabelValue;
    }
    connect() {
        this.debounceTimeout = null;
        this.searchResultConnection = new TurboStreamConnection();

        this.focusTrap = focusTrap.createFocusTrap(this.element, {
            escapeDeactivates: false,
            allowOutsideClick: true,
        });
    }

    disconnect() {
        this.#stopPendingSearch();
    }

    async search() {
        this.#stopPendingSearch();

        // Require a minimum number of characters
        if (this.inputTarget.value.length < this.minCharactersValue) {
            return this.#setState('invalid');
        }

        this.#setState('loading');

        // Debounce to avoid too many requests
        await new Promise((resolve) => (this.debounceTimeout = setTimeout(resolve, this.debounceDelayValue)));

        // Get the search results
        const result = await this.searchResultConnection.get(this.urlValue, { keywords: this.inputTarget.value });

        if (result.ok) {
            this.#setState('results');
            this.focusTrap.activate();
        } else if (result.error) {
            this.#setState('error');
        }
    }

    shortcutOpen(event) {
        const element = document.activeElement;

        if (
            element instanceof HTMLInputElement ||
            element instanceof HTMLTextAreaElement ||
            element instanceof HTMLSelectElement ||
            (element instanceof HTMLElement && element.isContentEditable)
        ) {
            return;
        }

        event.preventDefault();
        this.inputTarget.focus();
        this.#setState('initial');
    }

    open() {
        // Ignore focus on input if tabbing through results
        if (this.focusTrap.active) {
            return;
        }

        this.#setState('initial');
    }

    close(event) {
        // Only close when clicking away
        if (event instanceof PointerEvent && this.element.contains(event.target)) {
            return;
        }

        // Ignore lost focus on input when tabbing through results
        if (event.type === 'blur' && this.focusTrap.active) {
            return;
        }

        this.#stopPendingSearch();
        this.resultsTarget.innerText = '';

        this.inputTarget.blur();
        this.inputTarget.value = '';

        this.#setState('hidden');
    }

    setButtonActive(event) {
        const target = event.target;
        const siblings = target.closest('ul').querySelectorAll('button');

        for (const sibling of siblings) {
            sibling.classList.toggle('active', false);
        }

        target.addClass('active');
    }

    #stopPendingSearch() {
        clearTimeout(this.debounceTimeout);
        this.searchResultConnection.abortPending();
        this.focusTrap.deactivate();
    }

    #setState(state) {
        for (const className of BackendSearchController.classes) {
            this.element.classList.toggle(this[`${className}Class`], className === state);
        }
    }
}
