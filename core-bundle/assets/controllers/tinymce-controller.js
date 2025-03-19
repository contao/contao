import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    connect() {
        // Work around a bug in Safari where the transition to a new context
        // causes disconnect() to be called before connect(). If the element ID
        // is identical - which is the case when saving a record - this messes
        // up the initialization of the editor. To prevent this, we delay the
        // execution until the call stack has been cleared and all microtasks,
        // i.e. disconnect() calls, have been executed.
        queueMicrotask(() => this._connect());
    }

    _connect() {
        if (!this.element.tinymceConfig) {
            if (window.console) {
                console.error(
                    'No TinyMCE config was attached to the DOM element, expected an expando property called "tinymceConfig".',
                    this.element,
                );
            }

            return;
        }

        const config = this.element.tinymceConfig;
        config.target = this.element;

        tinymce?.init(config).then((editors) => {
            this.editorId = editors[0]?.id;

            // Fire a custom event when the editor finished initializing.
            this.dispatch('editor-loaded', { detail: { content: editors[0] ?? null } });
        });
    }

    disconnect() {
        tinymce?.get(this.editorId)?.remove();
    }

    beforeCache() {
        // Destroy TinyMCE before Turbo caches the page. It will be recreated
        // when the connect() call happens on the restored page.
        this.disconnect();

        // Remove the controller attribute. They will be re-added in the init
        // script of the be_tinyMCE.html5 template.
        this.element.removeAttribute('data-controller');
    }

    leave(event) {
        const editor = tinymce?.get(this.editorId);

        if (!editor || !editor.plugins.hasOwnProperty('autosave') || editor.isNotDirty) {
            return;
        }

        // Trigger a beforeunload event like when navigating away to capture the TinyMCE autosave message
        const delegate = document.createEvent('BeforeUnloadEvent');
        delegate.initEvent('beforeunload', false, true);

        if (!window.dispatchEvent(delegate) && !confirm(delegate.returnValue)) {
            event.preventDefault();
        }
    }
}
