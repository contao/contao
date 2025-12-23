import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['parent', 'count'];
    static values = { selector: String };

    connect() {
        const count = this.parentTarget.querySelectorAll(this.selectorValue).length;

        for (const el of this.countTargets) {
            el.innerText = count !== 0 ? count : '';
        }
    }
}
