import {Controller} from "@hotwired/stimulus"

export default class ChoicesController extends Controller {
    static targets = ['select'];

    selectTargetConnected(select) {
        select.choices = new Choices(select, {
            shouldSort: false,
            duplicateItemsAllowed: false,
            allowHTML: false,
            removeItemButton: true,
            searchEnabled: select.options.length > 7,
            classNames: {
                containerOuter: ['choices', ...Array.from(select.classList)],
                flippedState: ''
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
            removeItemLabelText: function (value) {
                return Contao.lang.removeItem.concat(' ').concat(value);
            },
        })
    }

    selectTargetDisconnected(select) {
        select.choices.destroy();
    }
}
