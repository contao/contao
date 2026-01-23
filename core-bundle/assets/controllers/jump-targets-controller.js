import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    #btn = null;
    #prevButton = null;
    #nextButton = null;
    #linksContainer = null;
    #links = [];
    #onScroll = () => this.#updateScrollButtonVisibility();
    #onResize = () => this.#updateScrollButtonVisibility();

    static targets = ['navigation', 'section'];

    static values = {
        prevLabel: String,
        nextLabel: String,
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
        this.#linksContainer.destroy();
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

        this.#linksContainer = document.createElement('ul');
        this.#linksContainer.append(this.#prevButton ?? this.#createScrollButton());

        this.#links = [];

        for (const el of this.sectionTargets) {
            const action = this.#btn.cloneNode();
            action.innerText = el.getAttribute(`data-${this.identifier}-label-value`);

            action.addEventListener('click', () => {
                this.dispatch('scrollto', { target: el });
                el.scrollIntoView();
            });

            const li = document.createElement('li');
            li.append(action);

            this.#links.push(li);
            this.#linksContainer.append(li);
        }

        this.#linksContainer.append(this.#nextButton ?? this.#createScrollButton(false));
        this.navigationTarget.replaceChildren(this.#linksContainer);

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
        btn.textContent = start ? this.prevLabelValue : this.nextLabelValue;
        btn.title = start ? this.prevLabelValue : this.nextLabelValue;

        btn.addEventListener('click', () => {
            const target = start ? this.#getPreviousSnapItem() : this.#getNextSnapItem();

            if (!target) {
                return;
            }

            let scrollAmount;

            if (start) {
                scrollAmount = target.offsetLeft - this.#prevButton.offsetWidth - this.navigationTarget.scrollLeft;
            } else {
                scrollAmount =
                    target.offsetLeft +
                    target.offsetWidth +
                    this.#nextButton.offsetWidth -
                    this.navigationTarget.clientWidth -
                    this.navigationTarget.scrollLeft;
            }

            this.navigationTarget.scrollBy({
                behavior: 'smooth',
                left: scrollAmount,
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

    #getNextSnapItem() {
        return (
            this.#links.find(
                (item) =>
                    item.offsetLeft + item.offsetWidth >
                    this.navigationTarget.scrollLeft + this.navigationTarget.clientWidth,
            ) ??
            this.#links[this.#links.length - 1] ??
            null
        );
    }

    #getPreviousSnapItem() {
        return (
            [...this.#links]
                .reverse()
                .find(
                    (item) =>
                        item.offsetLeft + item.offsetWidth <=
                        this.navigationTarget.scrollLeft + this.#prevButton.offsetWidth,
                ) ??
            this.#links[0] ??
            null
        );
    }
}
