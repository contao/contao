import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static values = {
        config: String
    }

    connect() {
        tinymce?.init(JSON.parse(this.configValue));
    }

    disconnect() {
        tinymce?.remove(this.element);
    }
}
