import { Controller } from '@hotwired/stimulus';

/**
 * Makes an input field available as an outlet for other controllers.
 */
export default class extends Controller {
    static values = {
        name: String,
    };

    get value() {
        return this.element.value;
    }

    set value(value) {
        this.element.value = value;
    }
}
