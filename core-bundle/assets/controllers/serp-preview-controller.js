import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static values = {
        id: String,
        trail: String,
        titleTag: String,
        fields: Object,
    };

    static targets = ['url', 'title', 'description'];

    sourceElements = new Map();

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
                el.addEventListener('input', this._update.bind(this, sourceType));
            }

            this.sourceElements.set(sourceType, elements);

            // Initially gather content
            this._update(sourceType);
        }
    }

    disconnect() {
        for (const elements of this.sourceElements.values()) {
            elements.forEach((el) => {
                el.removeEventListener('input', this._update);
            });
        }

        this.sourceElements.clear();
    }

    _update(sourceType) {
        const value = this._getValue(sourceType);

        if (sourceType === 'title') {
            this.titleTarget.textContent = this._shorten(
                this._html2string(this.titleTagValue.replace(/%s/, value)).replace(/%%/g, '%'),
                64,
            );
        } else if (sourceType === 'alias') {
            this.urlTarget.textContent =
                value === 'index'
                    ? this.trailValue
                    : `${this.trailValue} › ${(value || this.idValue).replace(/\//g, ' › ')}`;
        } else if (sourceType === 'description') {
            this.descriptionTarget.textContent = this._shorten(value, 160);
        }
    }

    _getValue(sourceType) {
        for (const el of this.sourceElements.get(sourceType)) {
            if (!el) {
                continue;
            }

            const value =
                el.classList.contains('tl_textarea') && el.classList.contains('noresize')
                    ? this._html2string(el.value)
                    : el.value;

            if (value) {
                return value;
            }
        }

        return '';
    }

    _shorten(str, max) {
        if (str.length <= max) {
            return str;
        }

        return str.substr(0, str.lastIndexOf(' ', max)) + ' …';
    }

    _html2string(html) {
        return new DOMParser()
            .parseFromString(html, 'text/html')
            .body.textContent.replace(/\[-]/g, '\xAD')
            .replace(/\[nbsp]/g, '\xA0');
    }
}
