window.addEventListener('DOMContentLoaded', function() {
    const storeState = function(id, table, state) {
        fetch(window.location.href, {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: new URLSearchParams({
                action: 'toggleFieldset',
                id: id,
                table: table,
                state: state,
                REQUEST_TOKEN: Contao.request_token
            })
        });
    };

    const toggleState = function(el, id, table) {
        el.blur();
        Backend.getScrollOffset();

        const fs = el.parentNode;

        if (fs.classList.contains('collapsed')) {
            fs.classList.remove('collapsed');
            storeState(id, table, 1);
        } else {
            const form = fs.closest('form');
            const inp = fs.querySelectorAll('[required]');
            let collapse = true;

            for (let i = 0; i < inp.length; i++) {
                if (!inp[i].value) {
                    collapse = false;
                    break;
                }
            }

            if (!collapse) {
                if (typeof(form.checkValidity) == 'function') form.querySelector('button[type="submit"]').click();
            } else {
                fs.classList.add('collapsed');
                storeState(id, table, 0);
            }
        }
    };

    document.querySelectorAll('legend[data-toggle-fieldset]').forEach(function(el) {
        const fs = el.parentNode;

        if (fs.querySelectorAll('label.error, label.mandatory').length) {
            fs.classList.remove('collapsed');
        } else if (fs.classList.contains('hide')) {
            fs.classList.add('collapsed');
        }

        const { id, table } = JSON.parse(el.getAttribute('data-toggle-fieldset'));

        el.addEventListener('click', function(event) {
            event.preventDefault();
            toggleState(el, id, table);
        })
    });

    AjaxRequest.toggleFieldset = function(el, id, table) {
        window.console && console.warn('Using AjaxRequest.toggleFieldset is deprecated and will be removed in Contao 6. Add the data-toggle-fieldset attribute instead.');
        toggleState(el, id, table);
    };
});
