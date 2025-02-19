import { Controller } from '@hotwired/stimulus';
import { TwigEditor } from '../modules/twig-editor';

export default class extends Controller {
    editors = new Map();

    static values = {
        followUrl: String,
        blockInfoUrl: String,
    };

    static targets = ['themeSelector', 'tabs', 'editor', 'editorAnnotations'];

    connect() {
        // Subscribe to events dispatched by the editors
        this.element.addEventListener('twig-editor:lens:follow', event => {
            this._visit(this.followUrlValue, {name: event.detail.name});
        });

        this.element.addEventListener('twig-editor:lens:block-info', event => {
            this._visit(this.blockInfoUrlValue, event.detail);
        });

        this.element.addEventListener('turbo:submit-start', event => {
            // Add the currently open editor tabs to the request when selecting a theme
            if (event.target === this.themeSelectorTarget) {
                this._addOpenEditorTabsToRequest(event);
            }

            // Include the active editor's content when the save operation was triggered
            if (event.detail.formSubmission.submitter?.dataset?.operation === 'save') {
                this._addEditorContentToRequest(event);
                this._getActiveMutableEditor()?.focus();
            }
        });
    }

    beforeCache() {
        // Destroy editor instances before Turbo caches the page. They will be
        // recreated when the editorTargetConnected() calls happens on the
        // restored page.
        for (const [key, editor] of this.editors) {
            editor.destroy();
            delete this.editors[key];
        }
    }

    close(event) {
        document.getElementById(event.target.getAttribute('aria-controls')).innerText = '';
    }

    editorTargetConnected(el) {
        this.editors.set(el, new TwigEditor(el.querySelector('textarea')));
    }

    editorTargetDisconnected(el) {
        this.editors.get(el).destroy();
        this.editors.delete(el);
    }

    editorAnnotationsTargetConnected(el) {
        this.editors
            .get(el.closest('*[data-contao--template-studio-target="editor"]'))
            ?.setAnnotationsData(JSON.parse(el.innerText))
        ;
    }

    colorChange(event) {
        this.editors.forEach(editor => {
            editor.setColorScheme(event.detail.mode);
        })
    }

    _addOpenEditorTabsToRequest(event) {
        const searchParams = event.detail.formSubmission.location.searchParams;

        const tabs = this.application
            .getControllerForElementAndIdentifier(this.tabsTarget, 'contao--tabs')
            .getTabs()
        ;

        Object.keys(tabs).forEach(tabId => {
            // Extract identifier from tabId "template-studio--tab_<identifier>"
            searchParams.append('open_tab[]', tabId.substring(21));
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

    async _visit(url, params) {
        if (params !== null) {
            url += '?' + new URLSearchParams(params).toString();
        }

        const response = await fetch(url, {
            method: 'get',
            headers: {
                'Accept': 'text/vnd.turbo-stream.html',
            }
        });

        if (response.redirected) {
            document.location = response.url;

            return;
        }

        if (!response.headers.get('content-type').startsWith('text/vnd.turbo-stream.html') || response.status >= 300) {
            if (window.console) {
                console.error(`There was an error processing the Turbo stream response from "${url}"`);
            }

            return;
        }

       const html = await response.text()
       Turbo.renderStreamMessage(html);
    }
}
