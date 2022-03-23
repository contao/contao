window.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('legend[data-toggle-fieldset]').forEach(function (el) {
        const fs = el.parentNode;
        if (fs.querySelectorAll('label.error, label.mandatory').length) {
            fs.classList.remove('collapsed');
        } else if (fs.classList.contains('hide')) {
            fs.classList.add('collapsed');
        }

        const { id, table } = JSON.parse(el.getAttribute('data-toggle-fieldset'));

        function storeState (state) {
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
        }

        el.addEventListener('click', function (event) {
            event.preventDefault();

            el.blur();
            Backend.getScrollOffset();

            if (fs.classList.contains('collapsed')) {
                fs.classList.remove('collapsed');
                storeState(1);
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
                    if (typeof (form.checkValidity) == 'function') form.querySelector('button[type="submit"]').click();
                } else {
                    fs.classList.add('collapsed');
                    storeState(0);
                }
            }
        })
    });
});
