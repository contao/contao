/*!
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */
(function () {
    let choicesList = [];

    function initChoices(node) {
        node.querySelectorAll('select.tl_chosen').forEach(function (select) {
            if (choicesList.includes(select)) {
                return;
            }

            choicesList.push(select);

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
                callbackOnInit: () => {
                   const choices = select.closest('.choices')?.querySelector('.choices__list--dropdown > .choices__list');
                   if (choices && select.dataset.placeholder) {
                       choices.dataset.placeholder = select.dataset.placeholder;
                   }
                }
            })
        });
    }

    initChoices(document);

    new MutationObserver(function (mutationsList) {
        for(const mutation of mutationsList) {
            if (mutation.type === 'childList') {
                mutation.addedNodes.forEach(function (element) {
                    if (element.querySelectorAll) {
                        initChoices(element)
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
