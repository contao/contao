(function(){
    'use strict';

    const initializedRows = new WeakMap();

    const init = (row) => {
        // Check if this row has already been initialized
        if (initializedRows.has(row)) {
            return;
        }

        // Check if this row has all necessary elements
        if (7 !== row.querySelectorAll('input, select, button').length) {
            return;
        }

        initializedRows.set(row, true);

        const tbody = row.closest('tbody');

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
        addEventsTo(row);
    };

    document.querySelectorAll('.tl_sectionwizard tr').forEach(init);

    new MutationObserver(function (mutationsList) {
        for (const mutation of mutationsList) {
            if (mutation.type === 'childList') {
                mutation.addedNodes.forEach(function (element) {
                    if (element.matches && element.matches('.tl_sectionwizard tr, .tl_sectionwizard tr *')) {
                        init(element.closest('tr'));
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
