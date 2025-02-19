import {Controller} from "@hotwired/stimulus"

export default class ChoicesController extends Controller {
    mutationGuard = false;

    connect() {
        if (this._isGuarded()) {
            return;
        }

        // Choices wraps the element multiple times during initialization, leading to
        // multiple disconnects/reconnects of the controller that we need to ignore.
        this._setGuard();

        const select = this.element;

        this.choices = new Choices(select, {
            shouldSort: false,
            duplicateItemsAllowed: false,
            allowHTML: false,
            removeItemButton: true,
            searchEnabled: select.options.length > 7,
            classNames: {
                containerOuter: ['choices', ...Array.from(select.classList)],
                flippedState: '',
            },
            fuseOptions: {
                includeScore: true,
                threshold: 0.4,
            },
            callbackOnInit: () => {
                const choices = select.closest('.choices')?.querySelector('.choices__list--dropdown > .choices__list');

                if (choices && select.dataset.placeholder) {
                    choices.dataset.placeholder = select.dataset.placeholder;
                }

                this._resetGuard();
            },
            loadingText: Contao.lang.loading,
            noResultsText: Contao.lang.noResults,
            noChoicesText: Contao.lang.noOptions,
            removeItemLabelText: function (value) {
                return Contao.lang.removeItem.concat(' ').concat(value);
            },
        });

        // Trigger a custom "choicesInit" event to allow third parties to interact with the Choices instance
        const initEvent = new CustomEvent('choicesInit', {
            detail: {
                choices: this.choices
            }
        });

        select.dispatchEvent(initEvent);
    }

    disconnect() {
        if (this._isGuarded()) {
            return;
        }

        this._removeChoices();
    }

    beforeCache() {
        // Let choices unwrap the element container before Turbo caches the
        // page. It will be recreated, when the connect() call happens on the
        // restored page.
        this._removeChoices();
    }

    _removeChoices() {
        // Safely unwrap the element by preventing disconnect/connect calls
        // during the process.
        this._setGuard();

        this.choices?.destroy();
        this.choices = null;

        this._resetGuard();
    }

    _setGuard() {
        this.mutationGuard = true;
    }

    _resetGuard() {
        // Reset guard as soon as the call stack has cleared.
        setTimeout(() => { this.mutationGuard = false; }, 0);
    }

    _isGuarded() {
        return this.mutationGuard;
    }
}
