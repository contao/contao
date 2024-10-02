import { Controller } from '@hotwired/stimulus';
import { TwigEditor } from '../modules/twig-editor';

export default class extends Controller {
    editors = new Map();

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
            Turbo.renderStreamMessage(html);
        })
        .catch((e) => {
            if (e.name !== 'AbortError') {
                console.error(e, e.type);
            }
        });
    }

    editorTargetConnected(el) {
        this.editors.set(el, new TwigEditor(el.querySelector('textarea')));
    }

    editorTargetDisconnected(el) {
        this.editors.get(el).destroy();
        this.editors.delete(el);
    }

    colorChange(event) {
        this.editors.forEach(editor => {
            editor.setColorScheme(event.detail.mode);
        })
    }
}
