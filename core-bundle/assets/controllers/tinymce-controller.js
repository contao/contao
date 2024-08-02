import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    connect() {
        if(!this.element.tinymceConfig) {
            console.error('No tinymce config was attached to the DOM element, expected an expando property called "tinymceConfig".', this.element);

            return;
        }

        this.config = this.element.tinymceConfig;
        delete this.element.tinymceConfig;

        tinymce?.init(this.config);
    }

    disconnect() {
        tinymce?.remove(this.config.selector);
    }
}
