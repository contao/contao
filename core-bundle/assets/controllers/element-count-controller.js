import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['parent', 'count'];
    static values = { selector: String };

    connect() {
        const count = this.parentTarget.querySelectorAll(this.selectorValue).length;

        this.countTargets.forEach(el => el.innerText = count !== 0 ? count : '');
    }
}
