import TextareaAutogrow from 'stimulus-textarea-autogrow';

export default class extends TextareaAutogrow {
    initialize() {
        if (this.#containsNoResizeClass) {
            return;
        }

        super.initialize();
    }

    connect() {
        if (this.#containsNoResizeClass()) {
            return;
        }

        super.connect();
    }

    #containsNoResizeClass() {
        return this.element.classList.contains('noresize');
    }
}
