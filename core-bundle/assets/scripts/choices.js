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
                searchEnabled: select.options.length > 7,
                classNames: {
                    containerOuter: 'choices ' + select.className,
                    flippedState: ''
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
