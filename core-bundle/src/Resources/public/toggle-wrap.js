(function () {
    const initialized = [];

    const toggleWrap = function(textarea) {
        const status = textarea.getAttribute('wrap') === 'off' ? 'soft' : 'off';
        textarea.setAttribute('wrap', status);
    };

    function init(node) {
        node.querySelectorAll('img.toggleWrap').forEach(function (el) {
            if (initialized.includes(el)) {
                return;
            }

            initialized.push(el);

            // Widget markup: h3 > img.toggleWrap // h3 + textarea
            const textarea = el.parentNode.nextElementSibling;

            if (textarea && textarea.tagName === 'TEXTAREA') {
                el.addEventListener('click', function (event) {
                    event.preventDefault();
                    toggleWrap(textarea)
                });
            }
        });
    }

    document.querySelectorAll('textarea.monospace').forEach(function(el) {
        toggleWrap(el);
    });

    init(document);
    new MutationObserver(function (mutationsList) {
        for(const mutation of mutationsList) {
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

    Backend.toggleWrap = function (id) {
        const textarea = document.querySelector(`#${id}`);
        if (textarea) {
            toggleWrap(textarea);
        }
    }
})();
