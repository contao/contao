import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    connect() {
        this.observer = new IntersectionObserver(this.#handleIntersect.bind(this), this.observerOptions);
        this.observer.observe(this.element);
    }

    disconnect() {
        this.observer.disconnect();
    }

    get observerOptions() {
        return {
            threshold: 1.0,
            rootMargin: '0px 0px -1px 0px',
        };
    }

    #handleIntersect(entries) {
        for (const entry of entries) {
            this.element.classList.toggle('is-stuck', !entry.isIntersecting);
        }
    }
}
