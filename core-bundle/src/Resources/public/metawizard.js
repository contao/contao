(function () {
    var initialized = [];

    function init(node) {
        node.querySelectorAll('.tl_metawizard [data-delete]').forEach(function (el) {
            if (initialized.includes(el)) {
                return;
            }

            initialized.push(el);

            el.addEventListener('click', function() {
                el.closest('li').querySelectorAll('input, textarea').forEach(function(input) {
                    input.value = '';
                });
            });
        });
    }

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
})();
