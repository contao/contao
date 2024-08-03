import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    initialize() {
        Turbo.cache.exemptPageFromCache();
    }

    connect() {
        if(!this.element.tinymceConfig) {
            console.error('No tinymce config was attached to the DOM element, expected an expando property called "tinymceConfig".', this.element);

            return;
        }

        // Move the config to a member variable to prevent memory leaks.
        this.config = {
            ...this.element.tinymceConfig,
            ...{
                target: this.element
            }
        };

        delete this.element.tinymceConfig;

        // TinyMCE creates the editor id based on the target element's id (if set).
        // This leads to a problem if an editor with the same id is still around,
        // which would be the case if the disconnect event cleaning up the resource
        // did not run, yet. We therefore remove the id and re-add it after
        // initializing the editor.
        const elementId = this.element.id;
        this.element.removeAttribute('id');

        tinymce?.init(this.config).then((editors) => {
            this.editorId = editors[0]?.id;
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

        // Trigger a beforeunload event like when navigating away to capture the tinyMCE autosave message
        const delegate = document.createEvent('BeforeUnloadEvent');
        delegate.initEvent('beforeunload', false, true);

        if(!window.dispatchEvent(delegate) && !confirm(delegate.returnValue)) {
            event.preventDefault();
        }
    }
}
