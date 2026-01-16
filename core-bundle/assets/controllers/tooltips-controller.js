import { Controller } from '@hotwired/stimulus';

export default class TooltipsController extends Controller {
    #tooltip = null;
    #timer = null;
    #activeTargets = new Set();
    #removeClickTargetHandlerDelegates = new Map();

    static defaultOptionsMap = {
        'a img[alt]': { x: -9, y: 30 },
        '.sgallery img[alt]': { x: 0, y: 75 },
        'p.tl_tip': { x: 0, y: 23, useContent: true },
        '#home[title]': { x: 6, y: 42 },
        '#tmenu a[title]': { x: 0, y: 42 },
        '#tmenu button[title]': { x: 0, y: 42 },
        'a[title][class^="group-"]': { x: -6, y: 27 },
        'a[title].navigation': { x: 25, y: 32 },
        'img[title].gimage': { x: -9, y: 60 },
        'img[title]:not(.gimage)': { x: -9, y: 30 },
        'a[title].picker-wizard': { x: -4, y: 30 },
        'button img[alt]': { x: -9, y: 30 },
        '.tl_panel button[title]': { x: 0, y: 36 },
        '.jump-target-scroll button[title]': { x: -4, y: 36 },
        'button[title].unselectable': { x: -4, y: 20 },
        'button[title]:not(.unselectable)': { x: -9, y: 30 },
        'a[title]:not(.picker-wizard)': { x: -9, y: 30 },
        'input[title]': { x: -9, y: 30 },
        'time[title]': { x: -9, y: 26 },
        'span[title]': { x: -9, y: 26 },
    };

    /**
     * There is one controller handling multiple tooltip targets. The tooltip
     * DOM element is shared across targets.
     */
    connect() {
        this.#tooltip = document.body.querySelector('body > div[role="tooltip"]') ?? this.#createTipContainer();
    }

    disconnect() {
        this.#tooltip.remove();
    }

    tooltipTargetConnected(el) {
        el.addEventListener('mouseenter', (e) => this.#showTooltip(e.target, 1000));
        el.addEventListener('touchend', (e) => this.#showTooltip(e.target));
        el.addEventListener('mouseleave', (e) => this.#hideTooltip(e.target));

        // In case the tooltip target is inside a link or button, also close it
        // when a click happened
        const clickTarget = el.closest('button, a');

        if (clickTarget) {
            const handler = () => this.#hideTooltip(el);

            clickTarget.addEventListener('click', handler);
            this.#removeClickTargetHandlerDelegates.set(el, () => el.removeEventListener('click', handler));
        }
    }

    tooltipTargetDisconnected(el) {
        if (this.#activeTargets.has(el)) {
            this.#hideTooltip(el);
        }

        if (this.#removeClickTargetHandlerDelegates.has(el)) {
            this.#removeClickTargetHandlerDelegates.get(el)();
            this.#removeClickTargetHandlerDelegates.delete(el);
        }
    }

    touchStart(e) {
        [...this.#activeTargets].filter((el) => !el.contains(e.target)).forEach(this.#hideTooltip.bind(this));
    }

    #createTipContainer() {
        const tooltip = document.createElement('div');
        tooltip.setAttribute('role', 'tooltip');
        tooltip.classList.add('tip');
        tooltip.style.position = 'absolute';
        tooltip.style.display = 'none';

        document.body.appendChild(tooltip);

        return tooltip;
    }

    #showTooltip(el, delay = 0) {
        const options = this.#getOptionsForElement(el);
        let text;

        if (options.useContent) {
            text = el.innerHTML;
        } else if (el instanceof HTMLImageElement) {
            text = el.getAttribute('alt');
            text = text?.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/"/g, '&quot;').replace(/'/g, '&apos;');
        } else {
            text = el.getAttribute('title');
            el.setAttribute('data-original-title', text);
            el.removeAttribute('title');
            text = text?.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/"/g, '&quot;').replace(/'/g, '&apos;');
        }

        if (!text) {
            return;
        }

        clearTimeout(this.#timer);
        this.#tooltip.style.willChange = 'display,contents';

        this.#timer = setTimeout(() => {
            this.#activeTargets.add(el);

            const position = el.getBoundingClientRect();
            const rtl = getComputedStyle(el).direction === 'rtl';
            const clientWidth = document.documentElement.clientWidth;

            if ((rtl && position.x < 200) || (!rtl && position.x < clientWidth - 200)) {
                this.#tooltip.style.left = `${window.scrollX + position.left + options.x}px`;
                this.#tooltip.style.right = 'auto';
                this.#tooltip.classList.remove('tip--rtl');
            } else {
                this.#tooltip.style.left = 'auto';
                this.#tooltip.style.right = `${clientWidth - window.scrollX - position.right + options.x}px`;
                this.#tooltip.classList.add('tip--rtl');
            }

            this.#tooltip.innerHTML = `<div>${text}</div>`;
            this.#tooltip.style.top = `${window.scrollY + position.top + options.y}px`;
            this.#tooltip.style.display = 'block';
            this.#tooltip.style.willChange = 'auto';
        }, delay);
    }

    #hideTooltip(el, delay = 0) {
        if (el.hasAttribute('data-original-title')) {
            if (!el.hasAttribute('title')) {
                el.setAttribute('title', el.getAttribute('data-original-title'));
            }

            el.removeAttribute('data-original-title');
        }

        clearTimeout(this.#timer);
        this.#tooltip.style.willChange = 'auto';

        if (this.#tooltip.style.display === 'block') {
            this.#activeTargets.delete(el);

            this.#tooltip.style.willChange = 'display';
            this.#timer = setTimeout(() => {
                this.#tooltip.style.display = 'none';
                this.#tooltip.style.willChange = 'auto';
            }, delay);
        }
    }

    #getOptionsForElement(el) {
        for (const [criteria, defaultOptions] of Object.entries(TooltipsController.defaultOptionsMap)) {
            if (el.matches(criteria)) {
                return defaultOptions;
            }
        }

        return { x: -9, y: 30 };
    }

    /**
     * Migrate legacy targets to proper controller targets.
     */
    static afterLoad(identifier, application) {
        const targetSelectors = Object.keys(TooltipsController.defaultOptionsMap);

        const migrateTarget = (el) => {
            for (const target of targetSelectors) {
                if (!el.hasAttribute(`data-${identifier}-target`) && el.matches(target)) {
                    el.setAttribute(`data-${identifier}-target`, 'tooltip');
                }

                for (const sel of el.querySelectorAll(target)) {
                    if (!sel.hasAttribute(`data-${identifier}-target`)) {
                        sel.setAttribute(`data-${identifier}-target`, 'tooltip');
                    }
                }
            }
        };

        new MutationObserver((mutationsList) => {
            for (const mutation of mutationsList) {
                if (mutation.type !== 'childList') {
                    continue;
                }

                for (const node of mutation.addedNodes) {
                    if (!(node instanceof HTMLElement)) {
                        continue;
                    }

                    migrateTarget(node);
                }
            }
        }).observe(document, {
            childList: true,
            subtree: true,
        });

        // Initially migrate all targets that are already in the DOM
        for (const el of document.querySelectorAll(targetSelectors.join(','))) {
            migrateTarget(el);
        }
    }
}
