import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    #sourceElements = new Map();

    static values = {
        id: String,
        trail: String,
        titleTag: String,
        fields: Object,
        warning: String,
    };

    static targets = ['url', 'title', 'description', 'robots'];

    connect() {
        // Install event listeners on the source fields
        for (const [sourceType, ids] of Object.entries(this.fieldsValue)) {
            const elements = [];

            for (const id of ids) {
                const el = document.getElementById(id);

                if (!el) {
                    continue;
                }

                elements.push(el);
                el.addEventListener('input', this.#update.bind(this, sourceType));
            }

            this.#sourceElements.set(sourceType, elements);

            // Initially gather content
            this.#update(sourceType);
        }
    }

    disconnect() {
        for (const elements of this.#sourceElements.values()) {
            for (const el of elements) {
                el.removeEventListener('input', this.#update);
            }
        }

        this.#sourceElements.clear();
    }

    #update(sourceType) {
        const value = this.#getValue(sourceType);

        if (sourceType === 'title') {
            this.titleTarget.textContent = this.#shorten(
                this.#html2string(this.titleTagValue.replace(/%s/, value)).replace(/%%/g, '%'),
                64,
            );
        } else if (sourceType === 'alias') {
            this.urlTarget.textContent =
                value === 'index'
                    ? this.trailValue
                    : `${this.trailValue} › ${(value || this.idValue).replace(/\//g, ' › ')}`;
        } else if (sourceType === 'description') {
            this.descriptionTarget.textContent = this.#shorten(value, 160);
        } else if (sourceType === 'robots') {
            this.element.classList.toggle('noindex', value.contains('noindex'));
        }
    }

    #getValue(sourceType) {
        for (const el of this.#sourceElements.get(sourceType)) {
            if (!el) {
                continue;
            }

            const value = el.classList.contains('tl_textarea') ? this.#html2string(el.value) : el.value;

            if (value) {
                return value;
            }
        }

        return '';
    }

    #shorten(str, max) {
        if (str.length <= max) {
            return str;
        }

        return `${str.substr(0, str.lastIndexOf(' ', max))} …`;
    }

    #html2string(html) {
        return new DOMParser()
            .parseFromString(html, 'text/html')
            .body.textContent.replace(/\[-]/g, '\xAD')
            .replace(/\[nbsp]/g, '\xA0');
    }
}
