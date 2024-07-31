import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static values = {
        config: Object
    }

    connect() {
        tinymce?.init(this.configValue);
    }

    disconnect() {
        tinymce?.remove(this.configValue.selector);
    }
}
