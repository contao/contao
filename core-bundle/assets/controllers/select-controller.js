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
        this.slimselect = new SlimSelect({
            select: this.element,
            ...this.optionsValue
        });
    }

    disconnect() {
        this.slimselect.destroy();
    }
}
