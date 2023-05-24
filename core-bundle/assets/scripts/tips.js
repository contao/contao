(function() {
    const initialized = [];

    const tip = document.createElement('div');
    tip.setAttribute('role', 'tooltip');
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
                el.setAttribute('data-original-title', text);
                el.removeAttribute('title')
            }

            if (!text) {
                return;
            }

            clearTimeout(timer);
            tip.style.willChange = 'display,contents';

            timer = setTimeout(function() {
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

                if (!tip.parentNode && document.body) {
                    document.body.append(tip);
                }
            }, 1000)
        })

        el.addEventListener('mouseleave', function() {
            if (el.hasAttribute('data-original-title')) {
                if (!el.hasAttribute('title')) {
                    el.setAttribute('title', el.getAttribute('data-original-title'));
                }

                el.removeAttribute('data-original-title')
            }

            clearTimeout(timer)
            tip.style.willChange = 'auto';

            if (tip.style.display === 'block') {
                tip.style.willChange = 'display';
                timer = setTimeout(function() {
                    tip.style.display = 'none';
                    tip.style.willChange = 'auto';
                }, 100)
            }
        })

        const action = el.closest('button, a');

        // Hide tooltip when clicking a button (usually an operation icon in a wizard widget)
        if (action) {
            action.addEventListener('click', function() {
                clearTimeout(timer);
                tip.style.display = 'none';
                tip.style.willChange = 'auto';
            })
        }
    }

    function select(node, selector) {
        if (node.matches(selector)) {
            return [node, ...node.querySelectorAll(selector)];
        }

        return node.querySelectorAll(selector);
    }

    function setup(node) {
        select(node, 'p.tl_tip').forEach(function(el) {
            init(el, 0, 23, true);
        });

        select(node, '#home').forEach(function(el) {
            init(el, 6, 42);
        });

        select(node, '#tmenu a[title]').forEach(function(el) {
            init(el, 0, 42);
        });

        select(node, 'a[title][class^="group-"]').forEach(function(el) {
            init(el, -6, 27);
        });

        select(node, 'a[title].navigation').forEach(function(el) {
            init(el, 25, 32);
        });

        select(node, 'img[title]').forEach(function(el) {
            init(el, -9, el.classList.contains('gimage') ? 60 : 30);
        });

        ['a[title]', 'input[title]', 'button[title]', 'time[title]', 'span[title]'].forEach(function(selector) {
            select(node, selector).forEach(function(el) {
                init(el, -9, ((selector === 'time[title]' || selector === 'span[title]') ? 26 : 30));
            });
        });
    }

    setup(document.documentElement);

    new MutationObserver(function(mutationsList) {
        for(const mutation of mutationsList) {
            if (mutation.type === 'childList') {
                mutation.addedNodes.forEach(function(element) {
                    if (element.matches && element.querySelectorAll) {
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
