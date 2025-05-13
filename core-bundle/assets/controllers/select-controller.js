import { Controller } from '@hotwired/stimulus';
import SlimSelect from 'slim-select';

export default class SelectController extends Controller {

    connect() {
        this.slimselect = new SlimSelect({
          select: this.element
        });
    }

    disconnect() {
        this.slimselect.destroy();
    }
}
