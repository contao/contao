(function () {
    'use strict';

    const initializedChoices = new WeakMap();

    const init = (select) => {
        // Check if this select has already been initialized
        if (initializedChoices.has(select)) {
            return;
        }

        initializedChoices.set(select, true);

        new Choices(select, {
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

    new MutationObserver(function (mutationsList) {
        for (const mutation of mutationsList) {
            if (mutation.type === 'childList') {
                mutation.addedNodes.forEach(function (element) {
                    if (element.matches && element.matches('select.init-choices')) {
                        init(element);
                    }
                })
            }
        }
    }).observe(document, {
        attributes: false,
        childList: true,
        subtree: true
    });
})();
