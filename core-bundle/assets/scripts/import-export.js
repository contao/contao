window.addEventListener('click', function(event) {
    const button = event.target?.closest('.export-row');
    if (!button) {
        return;
    }
    event.preventDefault();
    const id = new URLSearchParams(button.search).get('id');
    const token = new URLSearchParams(button.search).get('rt');
    const table = button.dataset.table;
    fetch('/contao/_export', {
        method: 'POST',

        headers: {},
        body: new URLSearchParams({ id, table, REQUEST_TOKEN: token }),
    }).then(
        (response) => response.ok ? response.text() : Promise.reject(response)
    ).then(
        (text) => {
            navigator.clipboard.writeText(text);
        }
    );
}, {capture: true});

window.addEventListener('paste', function(event) {
    const data = event.clipboardData.getData('text/plain');
    if (data.substr(0, 18) !== '{"contao_export":"') {
        return;
    }

    event.preventDefault();

    fetch('/contao/_import', {
        method: 'POST',

        headers: {},
        body: new URLSearchParams({ 'import': data, REQUEST_TOKEN: Contao.request_token }),
    }).then(
        (response) => response.ok ? response.json() : Promise.reject(response)
    ).then(
        (json) => {
            document.location.reload();
        }
    );
});
