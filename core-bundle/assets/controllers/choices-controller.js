import { Controller } from '@hotwired/stimulus';

export default class ChoicesController extends Controller {
    connect() {
        const select = this.element.querySelector('select');

        this.choices = new Choices(select, {
            shouldSort: false,
            duplicateItemsAllowed: false,
            allowHTML: false,
            removeItemButton: true,
            searchEnabled: select.options.length > 7,
            searchResultLimit: -1,
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
            },
            loadingText: Contao.lang.loading,
            noResultsText: Contao.lang.noResults,
            noChoicesText: Contao.lang.noOptions,
            removeItemLabelText: (value) => Contao.lang.removeItem.concat(' ').concat(value),
        });
    }

    disconnect() {
        this._removeChoices();
    }

    beforeCache() {
        // Let choices unwrap the element container before Turbo caches the
        // page. It will be recreated, when the connect() call happens on the
        // restored page.
        this._removeChoices();
    }

    _removeChoices() {
        this.choices?.destroy();
        this.choices = null;
    }
}
