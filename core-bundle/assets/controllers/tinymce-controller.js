import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    initialize() {
        Turbo.cache.exemptPageFromCache();
    }

    connect() {
        if (!this.element.tinymceConfig) {
            console.error('No TinyMCE config was attached to the DOM element, expected an expando property called "tinymceConfig".', this.element);
            return;
        }

        const config = this.element.tinymceConfig;
        config.target = this.element;

        // TinyMCE creates the editor ID based on the target element's ID (if set).
        // This leads to a problem if an editor with the same ID is still around,
        // which would be the case if the disconnect event cleaning up the resource
        // has not run yet. We therefore remove the ID and re-add it after
        // initializing the editor.
        const elementId = this.element.id;
        this.element.removeAttribute('id');

        tinymce?.init(config).then((editors) => {
            this.editorId = editors[0]?.id;

            // Fire a custom event when the editor finished intializing.
            this.dispatch('editor-loaded', { detail: { content: editors[0] ?? null } });
        });

        this.element.setAttribute('id', elementId);
    }

    disconnect() {
        tinymce?.get(this.editorId)?.remove();
    }

    leave(event) {
        const editor = tinymce?.get(this.editorId)

        if(!editor || !editor.plugins.hasOwnProperty('autosave') || editor.isNotDirty) {
            return;
        }

        // Trigger a beforeunload event like when navigating away to capture the TinyMCE autosave message
        const delegate = document.createEvent('BeforeUnloadEvent');
        delegate.initEvent('beforeunload', false, true);

        if(!window.dispatchEvent(delegate) && !confirm(delegate.returnValue)) {
            event.preventDefault();
        }
    }
}
