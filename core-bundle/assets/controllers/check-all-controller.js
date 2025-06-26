import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['source', 'input'];

    update() {
        const checked = this.sourceTarget.checked;

        for (const el of this.inputTargets) {
            el.checked = checked;
        }
    }
}
