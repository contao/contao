import {Controller} from "@hotwired/stimulus"

export default class ChoicesController extends Controller {
    connect() {
        if (this.initGuard || this.element.classList.contains('choices__input')) {
            return;
        }

        // Choices wraps the element multiple times during initialization, leading to
        // multiple disconnects/reconnects of the controller that we need to ignore.
        this.initGuard = true;

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

                // Reset guard as soon as the call stack has cleared
                setTimeout(() => { this.initGuard = false; }, 0);
            },
            loadingText: Contao.lang.loading,
            noResultsText: Contao.lang.noResults,
            noChoicesText: Contao.lang.noOptions,
            removeItemLabelText: function (value) {
                return Contao.lang.removeItem.concat(' ').concat(value);
            },
        })
    }

    disconnect() {
        if (this.initGuard) {
            return;
        }

        this.choices?.destroy();
        this.choices = null;
    }
}
