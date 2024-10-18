import { Controller } from '@hotwired/stimulus';
import { TwigEditor } from '../modules/twig-editor';

export default class extends Controller {
    editors = new Map();

    static values = {
        followUrl: String,
        blockInfoUrl: String,
    };

    static targets = ['tabs', 'editor'];

    connect() {
        // Subscribe to events dispatched by the editors
        this.element.addEventListener('twig-editor:lens:follow', event => {
            this._visit(this.followUrlValue, {name: event.detail.name});
        });

        this.element.addEventListener('twig-editor:lens:block-info', event => {
            this._visit(this.blockInfoUrlValue, event.detail);
        });

        // Include the active editor's content when the save operation was triggered
        this.element.addEventListener('turbo:submit-start', event => {
            if(event.detail.formSubmission.submitter.dataset?.operation === 'save') {
                this._addEditorContentToRequest(event);
            }
        });
    }

    closePanel(el) {
        el.target.closest('*[data-panel]').innerText = '';
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

    _addEditorContentToRequest(event) {
        event.detail.formSubmission.fetchRequest.body.append(
            'code',
            this._getActiveMutableEditor()?.getContent() ?? ''
        );
    }

    _getActiveMutableEditor() {
        const editorElementsOnActiveTab = this.application
            .getControllerForElementAndIdentifier(this.tabsTarget, 'contao--tabs')
            .getActiveTab()
            ?.querySelectorAll('*[data-contao--template-studio-target="editor"]')
        ;

        for (const el of editorElementsOnActiveTab ?? []) {
            const editor = this.editors.get(el);

            if (editor && editor.isEditable()) {
                return editor;
            }
        }

        return null;
    }

    _visit(url, params) {
        if (params !== null) {
            url += '?' + new URLSearchParams(params).toString();
        }

        Turbo.visit(url, {acceptsStreamResponse: true});
    }
}
