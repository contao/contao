(function () {
    const initialized = [];

    const tip = document.createElement('div');
    tip.setAttribute('role', 'tooltip');
    tip.setAttribute('aria-hidden', true);
    tip.classList.add('tip')
    tip.style.position = 'absolute';
    tip.style.display = 'none';

    const init = function(el, x, y, useContent) {
        if (initialized.includes(el)) {
            return;
        }

        initialized.push(el);

        let text, timer;

        el.addEventListener('mouseenter', function() {
            if (useContent) {
                text = el.innerHTML;
            } else {
                text = el.getAttribute('title');
                el.removeAttribute('title')
            }

            if (!text) {
                return;
            }

            clearTimeout(timer);
            tip.style.willChange = 'display,contents';

            timer = setTimeout(function () {
                const position = el.getBoundingClientRect();
                const rtl = getComputedStyle(el).direction === 'rtl';
                const clientWidth = document.html.clientWidth;

                if ((rtl && position.x < 200) || (!rtl && position.x < (clientWidth - 200))) {
                    tip.style.left = `${(window.scrollX + position.left + x)}px`;
                    tip.style.right = 'auto';
                    tip.classList.remove('tip--rtl')
                } else {
                    tip.style.left = 'auto';
                    tip.style.right = `${(clientWidth - window.scrollX - position.right + x)}px`;
                    tip.classList.add('tip--rtl')
                }

                tip.innerHTML = `<div>${text}</div>`;
                tip.style.top = `${(window.scrollY + position.top + y)}px`;
                tip.style.display = 'block';
                tip.style.willChange = 'auto';
                tip.setAttribute('aria-hidden', false);

                if (!tip.parentNode && document.body) {
                    document.body.append(tip);
                }
            }, 1000)
        })

        el.addEventListener('mouseleave', function () {
            if (!useContent && text && !el.hasAttribute('title')) {
                el.setAttribute('title', text);
            }

            clearTimeout(timer)
            tip.style.willChange = 'auto';

            if (tip.style.display === 'block') {
                tip.style.willChange = 'display';
                timer = setTimeout(function () {
                    tip.style.display = 'none';
                    tip.style.willChange = 'auto';
                    tip.setAttribute('aria-hidden', true);
                }, 100)
            }
        })

        const action = el.closest('button, a');

        // Hide tooltip when clicking a button (usually an operation icon in a wizard widget)
        if (action) {
            action.addEventListener('click', function () {
                clearTimeout(timer);
                tip.style.display = 'none';
                tip.style.willChange = 'auto';
                tip.setAttribute('aria-hidden', true);
            })
        }
    }

    function setup(node) {
        node.querySelectorAll('p.tl_tip').forEach(function (el) {
            init(el, 0, 23, true);
        });

        node.querySelectorAll('#home').forEach(function (el) {
            init(el, 6, 42);
        });

        node.querySelectorAll('#tmenu a[title]').forEach(function (el) {
            init(el, 0, 42);
        });

        node.querySelectorAll('a[title][class^="group-"]').forEach(function (el) {
            init(el, -6, 27);
        });

        node.querySelectorAll('a[title].navigation').forEach(function (el) {
            init(el, 25, 32);
        });

        node.querySelectorAll('img[title]').forEach(function (el) {
            init(el, -9, el.classList.contains('gimage') ? 60 : 30);
        });

        ['a[title]', 'input[title]', 'button[title]', 'time[title]', 'span[title]'].forEach(function(selector) {
            node.querySelectorAll(selector).forEach(function (el) {
                init(el, -9, ((selector === 'time[title]' || selector === 'span[title]') ? 26 : 30));
            });
        });
    }

    setup(document);

    new MutationObserver(function (mutationsList) {
        for(const mutation of mutationsList) {
            if (mutation.type === 'childList') {
                mutation.addedNodes.forEach(function (element) {
                    if (element.querySelectorAll) {
                        setup(element)
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
