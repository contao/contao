import { Controller } from '@hotwired/stimulus';
import SlimSelect from 'slim-select';

export default class SelectController extends Controller {
    static values = {
        options: Object
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
            searchPlaceholder: ' ',
            showSearch: select.options.length > 6,
            searchText: Contao.lang.noResults,
            searchingText: Contao.lang.loading,
        };

        if (placeholder) {
            select.ariaLabel = placeholder;
            settings.placeholderText = placeholder;
            settings.ariaLabel = placeholder;
        }

        this.optionsValue = Object.assign({ settings: settings }, this.optionsValue);
    }
}
