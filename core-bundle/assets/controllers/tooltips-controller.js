import { Controller } from '@hotwired/stimulus';
import * as Position from '../modules/position';

export default class extends Controller {
    #timer = null;
    #current = null;
    #targetSelector = null;
    #contentTargetSelector = null;

    static targets = ['tooltip', 'content', 'popup', 'popupContent', 'popupArrow'];

    initialize() {
        this.#contentTargetSelector = `[data-${this.identifier}-target~="content"]`;
        this.#targetSelector = `[data-${this.identifier}-target~="tooltip"], ${this.#contentTargetSelector}`;
    }

    connect() {
        this.element.addEventListener('mouseover', this.#show);
        this.element.addEventListener('mouseout', this.#hide);
        this.element.addEventListener('focusin', this.#show);
        this.element.addEventListener('focusout', this.#hide);
        this.element.addEventListener('touchend', this.#show);
        this.element.addEventListener('click', this.#hide);
    }

    disconnect() {
        this.element.removeEventListener('mouseover', this.#show);
        this.element.removeEventListener('mouseout', this.#hide);
        this.element.removeEventListener('focusin', this.#show);
        this.element.removeEventListener('focusout', this.#hide);
        this.element.removeEventListener('touchend', this.#show);
        this.element.removeEventListener('click', this.#hide);

        this.#hide();
    }

    tooltipTargetDisconnected(el) {
        if (el === this.#current) {
            this.#hide();
        }
    }

    contentTargetDisconnected(el) {
        if (el === this.#current) {
            this.#hide();
        }
    }

    #show = (event) => {
        if (!(event.target instanceof Element)) {
            return;
        }

        const el = event.target.closest(this.#targetSelector);

        if (!el || el === this.#current) {
            return;
        }

        this.#hide();

        if (!this.#updateContent(el)) {
            return;
        }

        this.#migrateElementTitle(el, true);
        this.#current = el;

        const delay = { mouseover: 1000, focusin: 500 }[event.type] ?? 0;

        this.#timer = setTimeout(() => {
            Position.compute(el, this.popupTarget, this.popupArrowTarget);
            this.popupTarget.style.display = 'block';
        }, delay);
    };

    #hide = (event = null) => {
        if (this.#current === null) {
            return;
        }

        // Ignore mouseout events that only move within the current element
        if (event?.type === 'mouseout' && this.#current.contains(event.relatedTarget)) {
            return;
        }

        clearTimeout(this.#timer);
        this.#migrateElementTitle(this.#current, false);
        this.#current = null;
        this.popupTarget.style.display = 'none';
    };

    #updateContent(el) {
        if (el.matches(this.#contentTargetSelector)) {
            this.popupContentTarget.innerHTML = el.innerHTML;

            return this.popupContentTarget.textContent.trim() !== '';
        }

        const text = this.#contentFor(el);
        this.popupContentTarget.textContent = text;

        return text !== '';
    }

    #contentFor(el) {
        return (
            el.getAttribute('data-tooltip') ??
            el.getAttribute('aria-label') ??
            el.getAttribute('title') ??
            el.querySelector('img[alt]')?.getAttribute('alt') ??
            (el instanceof HTMLImageElement ? el.getAttribute('alt') : null) ??
            ''
        );
    }

    #migrateElementTitle(el, setTitle) {
        if (!el) {
            return;
        }

        if (setTitle && el.hasAttribute('title')) {
            el.setAttribute('data-original-title', el.getAttribute('title'));
            el.removeAttribute('title');
        } else if (!setTitle && el.hasAttribute('data-original-title')) {
            el.setAttribute('title', el.getAttribute('data-original-title'));
            el.removeAttribute('data-original-title');
        }
    }
}
