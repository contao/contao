import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['message'];

    messageTargetConnected(el) {
        const duration = el.classList.contains('message--error') ? 10000 : 5000;

        el.style.animationDuration = `${duration}ms`;

        setTimeout(() => el.remove(), duration);
    }
}
