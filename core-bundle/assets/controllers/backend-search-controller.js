import { Controller } from '@hotwired/stimulus';

export default class BackendSearchController extends Controller {
    static targets = ['input', 'results'];

    static values = {
        route: String,
        minCharacters: {
            type: Number,
            default: 3,
        },
        delay: {
            type: Number,
            default: 300,
        },
    };

    static classes = ['hidden', 'initial', 'loading', 'invalid', 'results', 'error'];

    connect() {
        this.active = false;
        this.timeout = null;

        this._initAbortController();
        this.setState('hidden');
    }

    performSearch() {
        if (this.inputTarget.value.length < this.minCharactersValue) {
            return this.setState('invalid');
        }

        clearTimeout(this.timeout);

        this.timeout = setTimeout(() => {
            this.loadResults();
        }, this.delayValue);
    }

    loadResults() {
        this.setState('loading');
        this._initAbortController();

        fetch(this.searchRoute, { signal: this.signal })
            .then((res) => {
                if (!res.ok) {
                    throw new Error(res.statusText);
                }

                return res.text();
            })
            .then((html) => {
                this.resultsTarget.innerHTML = html;
                this.setState('results');
            })
            .catch((e) => {
                if ('AbortError' === e.name) {
                    return;
                }

                this.setState('error');
            });
    }

    open() {
        if (!this.active) {
            this.setState('initial');
            this.active = true;
        }
    }

    close() {
        this.inputTarget.blur();
        this.inputTarget.value = '';

        this.active = false;
        this.timeout = null;

        this.setState('hidden');
        this._resetFocusableResults();
    }

    inputBlur() {
        if ('results' === this.state) {
            return;
        }

        this.close();
    }

    focusTrapNext(event) {
        if ('results' === this.state && document.activeElement === this.lastFocus) {
            event.preventDefault();
            this.firstFocus?.focus();
        }
    }

    focusTrapPrev(event) {
        if ('results' === this.state && document.activeElement === this.firstFocus) {
            event.preventDefault();
            this.lastFocus?.focus();
        }
    }

    _initAbortController() {
        this.abortController?.abort();
        this.abortController = new AbortController();
        this.signal = this.abortController?.signal;
    }

    _resetFocusableResults() {
        this.firstFocus = null;
        this.lastFocus = null;
    }

    _setFocusableResults() {
        const elements = [
            this.inputTarget,
            ...this.resultsTarget.querySelectorAll('a[href]:not([disabled]), button:not([disabled])'),
        ];

        this.firstFocus = elements[0] ?? [];
        this.lastFocus = elements[elements.length - 1] ?? [];
    }

    documentClick(event) {
        if (this.element.contains(event.target)) {
            return;
        }

        this.close();
    }

    setState(state) {
        this.state = state;

        if (state === 'results') {
            this._setFocusableResults();
        } else {
            this._resetFocusableResults();
        }

        BackendSearchController.classes.forEach((className) => {
            this.element.classList.toggle(this[`${className}Class`], className === state);
        });
    }

    get searchRoute() {
        return this.routeValue + this.inputTarget.value;
    }
}
