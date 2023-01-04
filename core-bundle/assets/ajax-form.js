window.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-ajax-form]').forEach(el => {
        function request(form, body, callback) {
            const xhr = new XMLHttpRequest();

            xhr.open('POST', form.action, true);
            xhr.setRequestHeader('Accept', 'text/html');
            xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
            xhr.setRequestHeader('X-Contao-Ajax-Form', form.querySelector('[name="FORM_SUBMIT"]').value);

            form.ariaBusy = 'true';
            form.dataset.ajaxForm = 'loading';

            xhr.onload = () => {
                form.dispatchEvent(new CustomEvent('ajax-form-onload', {
                    bubbles: true,
                    detail: { body, form, xhr },
                }));

                form.ariaBusy = 'false';
                form.dataset.ajaxForm = '';

                callback(xhr);
            };

            xhr.send(body || null)
        }

        function initForm(form) {
            form.addEventListener('submit', e => {
                e.preventDefault();

                const formData = new FormData(form);

                // Send the triggered button data as well
                if (e.submitter) {
                    formData.append(e.submitter.name, e.submitter.value);
                }

                request(form, formData, xhr => {
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
        }

        initForm(el);
    });
});
