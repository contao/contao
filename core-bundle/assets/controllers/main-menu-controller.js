import { Controller } from '@hotwired/stimulus';
import DropdownController from './dropdown-controller';

export default class extends DropdownController {
    static values = {
        bodyClass: {
            type: String,
            default: 'show-navigation',
        },
    }

    toggle() {
        const isOpen = this.buttonTarget.ariaExpanded === 'true';

        document.body.classList.toggle(this.bodyClassValue, !isOpen);

        super.toggleState(!isOpen);
    }
}
