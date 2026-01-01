import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    #btn = null;
    #prevButton = null;
    #nextButton = null;
    #links = null;
    #onScroll = () => this.#updateScrollButtonVisibility();
    #onResize = () => this.#updateScrollButtonVisibility();

    static targets = ['navigation', 'section'];

    static values = {
        prevLabel: String,
        nextLabel: String,
        scrollBy: Number,
    };

    initialize() {
        this.#initButtonElement();
    }

    navigationTargetConnected(element) {
        element.addEventListener('scroll', this.#onScroll);
        window.addEventListener('resize', this.#onResize);
    }

    navigationTargetDisconnected(element) {
        element.removeEventListener('scroll', this.#onScroll);
        window.removeEventListener('resize', this.#onResize);
        this.#links.destroy();
    }

    sectionTargetConnected() {
        this.rebuildNavigation();
    }

    sectionTargetDisconnected() {
        this.rebuildNavigation();
    }

    rebuildNavigation() {
        if (!this.hasNavigationTarget) {
            return;
        }

        this.#links = document.createElement('ul');

        // BC (having implemented the structure without the values)
        if (this.hasScrollByValue) {
            this.#links.append(this.#prevButton ?? this.#createScrollButton());
        }

        for (const el of this.sectionTargets) {
            const action = this.#btn.cloneNode();
            action.innerText = el.getAttribute(`data-${this.identifier}-label-value`);

            action.addEventListener('click', () => {
                this.dispatch('scrollto', { target: el });
                el.scrollIntoView();
            });

            const li = document.createElement('li');
            li.append(action);

            this.#links.append(li);
        }

        if (this.hasScrollByValue) {
            this.#links.append(this.#nextButton ?? this.#createScrollButton(false));
        }

        this.navigationTarget.replaceChildren(this.#links);

        this.#updateScrollButtonVisibility();
    }

    #updateScrollButtonVisibility() {
        if (!this.hasNavigationTarget) {
            return;
        }

        const el = this.navigationTarget;
        const maxScroll = el.scrollWidth - el.clientWidth;

        if (this.#prevButton) {
            this.#prevButton.classList.toggle('is-visible', el.scrollLeft > 0);
        }

        if (this.#nextButton) {
            this.#nextButton.classList.toggle('is-visible', el.scrollLeft < maxScroll);
        }
    }

    #createScrollButton(start = true) {
        const li = document.createElement('li');
        li.classList.add('jump-target-scroll');

        const btn = this.#btn.cloneNode();
        btn.textContent = start ? this.nextLabelValue : this.prevLabelValue;

        btn.addEventListener('click', () => {
            this.navigationTarget.scrollBy({
                behavior: 'smooth',
                left: this.scrollByValue * (start ? -1 : 1),
            });
        });

        li.append(btn);

        start ? (this.#prevButton = li) : (this.#nextButton = li);

        return li;
    }

    #initButtonElement() {
        this.#btn = document.createElement('button');
        this.#btn.type = 'button';
    }
}
