import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['editor'];

    openTab(el) {
        fetch(el.currentTarget.dataset.url, {
            method: 'GET',
            headers: {
                'Accept': 'text/vnd.turbo-stream.html',
            },
        })
        .then(response => response.text())
        .then(html => {
            Turbo.renderStreamMessage(html)
        })
        .catch((e) => {
            if (e.name !== 'AbortError') {
                console.error(e, e.type);
            }
        });
    }
}
