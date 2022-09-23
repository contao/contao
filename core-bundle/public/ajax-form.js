window.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-ajax-form]').forEach(form => {
        let triggeredButton = null;

        function request(method, uri, body, callback) {
            const xhr = new XMLHttpRequest();

            xhr.open(method, uri, true);
            xhr.setRequestHeader('Accept', 'text/html');
            xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
            xhr.setRequestHeader('X-Contao-Ajax-Form', form.querySelector('[name="FORM_SUBMIT"]').value);

            form.ariaBusy = 'true';
            form.dataset.ajaxForm = 'loading';

            xhr.onload = () => {
                callback(xhr);

                form.ariaBusy = 'false';
                form.dataset.ajaxForm = '';

                const event = new CustomEvent('ajax-form-onload', {
                    detail: { body, form, xhr },
                });

                form.dispatchEvent(event);
                window.dispatchEvent(event);
            };

            xhr.send(body || null)
        }

        function initForm(form) {
            form.addEventListener('submit', e => {
                e.preventDefault();

                const formData = new FormData(form);

                // Send the triggered button data as well
                if (triggeredButton) {
                    formData.append(triggeredButton.name, triggeredButton.value);
                }

                request('POST', form.action, formData, xhr => {
                    const location = xhr.getResponseHeader('X-Ajax-Location');

                    // Handle the redirect header
                    if (location) {
                        window.location.href = location;
                        return;
                    }

                    const template = document.createElement('template');
                    template.innerHTML = xhr.responseText.trim();

                    const newForm = template.content.firstElementChild;
                    form.replaceWith(newForm);
                    initForm(newForm);
                });
            });

            form.querySelectorAll('input[type="submit"], button[type="submit"]').forEach(el => {
                el.addEventListener('click', e => triggeredButton = e.currentTarget);
            });
        }

        initForm(form);
    });
});
