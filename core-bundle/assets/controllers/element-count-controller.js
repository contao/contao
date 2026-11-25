import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['parent', 'count'];
    static values = { selector: String };

    connect() {
        const count =
            this.hasParentTarget && this.selectorValue
                ? this.parentTarget.querySelectorAll(this.selectorValue).length
                : 0;

        for (const el of this.countTargets) {
            el.textContent = count !== 0 ? count : '';
        }
    }
}
