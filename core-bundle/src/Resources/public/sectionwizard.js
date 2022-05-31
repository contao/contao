(function(){
    'use strict';

    const initialized = new WeakMap();

    const init = (node) => {
        node.querySelectorAll('.tl_sectionwizard').forEach((table) => {
            if (initialized.has(table)) {
                return;
            }

            initialized.set(table, true);

            const tbody = table.querySelector('tbody');

            const makeSortable = (tbody) => {
                Array.from(tbody.children).forEach((tr, i) => {
                    tr.querySelectorAll('input, select').forEach((el) => {
                        el.name = el.name.replace(/\[[0-9]+]/g, '[' + i + ']');
                    });
                });

                // TODO: replace this with a vanilla JS solution
                new Sortables(tbody, {
                    constrain: true,
                    opacity: 0.6,
                    handle: '.drag-handle',
                    onComplete: function() {
                        makeSortable(tbody);
                    }
                });
            };

            const addEventsTo = (tr) => {
                tr.querySelectorAll('button').forEach((bt) => {
                    const command = bt.dataset.command;

                    switch (command) {
                        case 'copy':
                            bt.addEventListener('click', () => {
                                Backend.getScrollOffset();
                                const ntr = tr.cloneNode(true);
                                const selects = tr.querySelectorAll('select');
                                const nselects = ntr.querySelectorAll('select');
                                for (let j=0; j<selects.length; j++) {
                                    nselects[j].value = selects[j].value;
                                }
                                tr.parentNode.insertBefore(ntr, tr.nextSibling);
                                addEventsTo(ntr);
                                makeSortable(tbody);
                            });
                            break;

                        case 'delete':
                            bt.addEventListener('click', () => {
                                Backend.getScrollOffset();
                                if (tbody.children.length > 1) {
                                    tr.remove();
                                } else {
                                    // Reset values for last element (#689)
                                    tr.querySelectorAll('input').forEach((input) => {
                                        input.value = '';
                                    });

                                    tr.querySelectorAll('select').forEach((select) => {
                                        select.value = select.children[0].value;
                                    });
                                }
                                makeSortable(tbody);
                            });
                            break;

                        default:
                            if (bt.classList.contains('drag-handle')) {
                                bt.addEventListener('keydown', (event) => {
                                    if (event.code === 'ArrowUp' || event.keyCode === 38) {
                                        event.preventDefault();
                                        if (tr.previousElementSibling) {
                                            tr.previousElementSibling.insertAdjacentElement('beforebegin', tr);
                                        } else {
                                            tbody.insertAdjacentElement('beforeend', tr);
                                        }
                                        bt.focus();
                                        makeSortable(tbody);
                                    } else if (event.code === 'ArrowDown' || event.keyCode === 40) {
                                        event.preventDefault();
                                        if (tr.nextElementSibling) {
                                            tr.nextElementSibling.insertAdjacentElement('afterend', tr);
                                        } else {
                                            tbody.insertAdjacentElement('afterbegin', tr);
                                        }
                                        bt.focus();
                                        makeSortable(tbody);
                                    }
                                });
                            }
                            break;
                    }
                });
            };

            makeSortable(tbody);

            Array.from(tbody.children).forEach((tr) => {
                addEventsTo(tr);
            });
        });
    };

    new MutationObserver(function (mutationsList) {
        for (const mutation of mutationsList) {
            if (mutation.type === 'childList') {
                mutation.addedNodes.forEach(function (element) {
                    if (element.querySelectorAll) {
                        init(element)
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
