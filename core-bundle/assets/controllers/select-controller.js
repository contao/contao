import { Controller } from '@hotwired/stimulus';
import SlimSelect from 'slim-select';

export default class SelectController extends Controller {
    static values = {
        options: {
            type: Object,
            default: {}
        }
    }

    connect() {
        this._prepare();

        this.slimselect = new SlimSelect({
            select: this.element,
            ...this.optionsValue
        });
    }

    disconnect() {
        this.slimselect.destroy();
    }

    _prepare() {
        const select = this.element;
        const placeholder = select.dataset.placeholder;

        let settings = {
            showSearch: select.options.length > 6,
        };

        if (placeholder) {
            // Omit the `---` to show the placeholder
            if (select.options[0]?.innerText === '---') {
                select.options[0].innerText = '';
            }

            settings.placeholderText = placeholder;
        }

        this.optionsValue = Object.assign({ settings: settings }, this.optionsValue);
    }
}
