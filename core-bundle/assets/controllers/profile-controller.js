import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['button', 'menu'];

    static values = {
        name: { type: String, default: 'tmenu__profile' }
    }

    buttonTargetConnected(button) {
        button.setAttribute('aria-controls', this.nameValue);
        button.setAttribute('aria-expanded', 'false');
    }

    menuTargetConnected(menu) {
        menu.setAttribute('id', this.nameValue);
    }

    toggle(event) {
        event.stopPropagation();

        this.menuTarget.classList.toggle('active');

        if (this.menuTarget.classList.contains('active')) {
            this.buttonTarget.setAttribute('aria-expanded', 'true');
        } else {
            this.buttonTarget.setAttribute('aria-expanded', 'false');
        }
    }

    close() {
        this.menuTarget.classList.remove('active');
        this.buttonTarget.setAttribute('aria-expanded', 'false');
    }

    documentClick(event) {
        if (this.buttonTarget.contains(event.target)) {
            return;
        }

        this.close();
    }
}
