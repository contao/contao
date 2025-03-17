import {Controller} from '@hotwired/stimulus'
import {TurboStreamConnection} from '../modules/turbo-stream-connection';
import * as focusTrap from 'focus-trap'

export default class BackendSearchController extends Controller {
    static targets = ['input', 'results'];

    static values = {
        url: String,
        minCharacters: Number,
        debounceDelay: Number,
    }

    static classes = ['hidden', 'initial', 'loading', 'invalid', 'results', 'error'];

    connect() {
        this.debounceTimeout = null;
        this.searchResultConnection = new TurboStreamConnection();

        this.focusTrap = focusTrap.createFocusTrap(this.element, {
            escapeDeactivates: false,
            allowOutsideClick: true,
        });
    }

    disconnect() {
        this._stopPendingSearch();
    }

    async search() {
        this._stopPendingSearch();

        // Require a minimum amount of characters
        if (this.inputTarget.value.length < this.minCharactersValue) {
            return this._setState('invalid');
        }

        this._setState('loading');

        // Debounce to avoid too many requests
        await new Promise(resolve => this.debounceTimeout = setTimeout(resolve, this.debounceDelayValue));

        // Get the search results
        const result = await this.searchResultConnection.get(
            this.urlValue, {keywords: this.inputTarget.value}
        );

        if (result.ok) {
            this._setState('results');
            this.focusTrap.activate();
        } else if (result.error) {
            this._setState('error');
        }
    }

    open() {
        // Ignore focus on input if tabbing through results
        if (this.focusTrap.active) {
            return;
        }

        this._setState('initial');
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

        this._stopPendingSearch();
        this.resultsTarget.innerText = '';

        this.inputTarget.blur();
        this.inputTarget.value = '';

        this._setState('hidden');
    }

    _stopPendingSearch() {
        clearTimeout(this.debounceTimeout);
        this.searchResultConnection.abortPending();
        this.focusTrap.deactivate();
    }

    _setState(state) {
        BackendSearchController.classes.forEach(className => {
            this.element.classList.toggle(this[`${className}Class`], className === state);
        });
    }
}
