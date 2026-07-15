import { Controller } from '@hotwired/stimulus';
import * as Position from '../modules/position';

export default class extends Controller {
    #timer = null;
    #current = null;
    #pointer = null;
    #targetSelector = null;
    #contentTargetSelector = null;

    static targets = ['tooltip', 'content', 'popup', 'popupContent'];

    initialize() {
        this.#contentTargetSelector = `[data-${this.identifier}-target~="content"]`;
        this.#targetSelector = `[data-${this.identifier}-target~="tooltip"], ${this.#contentTargetSelector}`;
    }

    connect() {
        this.element.addEventListener('pointerover', this.#show);
        this.element.addEventListener('pointermove', this.#setPosition);
        this.element.addEventListener('pointerout', this.#hide);
        this.element.addEventListener('click', this.#hide);
    }

    disconnect() {
        this.element.removeEventListener('pointerover', this.#show);
        this.element.removeEventListener('pointermove', this.#setPosition);
        this.element.removeEventListener('pointerout', this.#hide);
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
        // Bail on touch devices
        if ('touch' === event.pointerType) return;

        const el = event.target instanceof Element ? event.target.closest(this.#targetSelector) : null;

        if (!el || el === this.#current) {
            return;
        }

        this.#hide();

        if (!this.#updateContent(el)) {
            return;
        }

        this.#migrateElementTitle(el, true);
        this.#current = el;

        this.#pointer = { x: event.clientX, y: event.clientY };

        this.#timer = setTimeout(() => {
            this.#positionTooltip();
            this.popupTarget.style.display = 'block';
        }, 1000);
    };

    #setPosition = (event) => {
        if ('touch' === event.pointerType) return;

        this.#pointer = { x: event.clientX, y: event.clientY };

        if (this.#current && this.popupTarget.style.display === 'block') {
            this.#positionTooltip();
        }
    };

    #hide = (event = null) => {
        if (this.#current === null) {
            return;
        }

        // Ignore pointerout events that only move within the current element
        if (event?.type === 'pointerout' && this.#current.contains(event.relatedTarget)) {
            return;
        }

        clearTimeout(this.#timer);
        this.#migrateElementTitle(this.#current, false);
        this.#current = null;
        this.popupTarget.style.display = 'none';
    };

    #positionTooltip() {
        const anchor = Position.pointerAnchor(this.#pointer.x, this.#pointer.y);

        Position.compute(anchor, this.popupTarget, null, 'bottom-start', 18);
    }

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
        if (setTitle && el.hasAttribute('title')) {
            el.setAttribute('data-original-title', el.getAttribute('title'));
            el.removeAttribute('title');
        } else if (!setTitle && el.hasAttribute('data-original-title')) {
            el.setAttribute('title', el.getAttribute('data-original-title'));
            el.removeAttribute('data-original-title');
        }
    }
}
