import { Controller } from '@hotwired/stimulus';
import * as Position from '../modules/position';

export default class TooltipsController extends Controller {
    #tooltip = null;
    #tooltipContainer = null;
    #tooltipArrow = null;
    #timer = null;
    #activeTargets = new Set();
    #removeClickTargetHandlerDelegates = new Map();

    static htmlElements = ['p.tl_tip'];

    static elements = [
        'a img[alt]',
        '.sgallery img[alt]',
        'p.tl_tip',
        '#home[title]',
        '#tmenu a[title]',
        '#tmenu button[title]',
        'a[title][class^="group-"]',
        'a[title].navigation',
        'img[title].gimage',
        'img[title]:not(.gimage)',
        'a[title].picker-wizard',
        'button img[alt]',
        '.tl_panel button[title]',
        '.jump-target-scroll button[title]',
        'button[title].unselectable',
        'button[title]:not(.unselectable)',
        'a[title]:not(.picker-wizard)',
        'input[title]',
        'time[title]',
        'span[title]',
    ];

    /**
     * There is one controller handling multiple tooltip targets. The tooltip
     * DOM element is shared across targets.
     */
    connect() {
        if (null === this.#tooltip) {
            this.#createTipContainer();
        }
    }

    disconnect() {
        this.#destroyTipContainer();
    }

    tooltipTargetConnected(el) {
        el.addEventListener('mouseenter', this.#showTooltip.bind(this, el, null, 1000));
        el.addEventListener('touchend', this.#showTooltip.bind(this, el, null, 0));
        el.addEventListener('mouseleave', this.#hideTooltip.bind(this, el));

        const clickTarget = el.closest('button, a');

        if (clickTarget) {
            clickTarget.addEventListener('focus', this.#showTooltip.bind(this, el, clickTarget, 500));
            clickTarget.addEventListener('blur', this.#hideTooltip.bind(this, el));

            // In case the tooltip target is inside a link or button, also close it
            // when a click happened
            const handler = () => this.#hideTooltip(el);

            clickTarget.addEventListener('click', handler);
            this.#removeClickTargetHandlerDelegates.set(el, () => {
                el.removeEventListener('click', handler);
                el.removeEventListener('blur', this.#hideTooltip.bind(this));
            });
        } else {
            el.addEventListener('focus', this.#showTooltip.bind(this, el, null, 500));
            el.addEventListener('blur', this.#hideTooltip.bind(this, el));
        }
    }

    tooltipTargetDisconnected(el) {
        el.removeEventListener('mouseenter', this.#showTooltip);
        el.removeEventListener('focus', this.#showTooltip);
        el.removeEventListener('touchend', this.#showTooltip);
        el.removeEventListener('mouseleave', this.#hideTooltip);
        el.removeEventListener('blur', this.#hideTooltip);

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

    #includeHtml(el) {
        for (const selector of TooltipsController.htmlElements) {
            if (el.matches(selector)) {
                return true;
            }
        }

        return false;
    }

    #createTipContainer() {
        this.#tooltip = document.createElement('div');
        this.#tooltip.setAttribute('role', 'tooltip');
        this.#tooltip.id = 'tooltip';

        this.#tooltipContainer = document.createElement('div');
        this.#tooltipContainer.id = 'tooltip_content';

        this.#tooltipArrow = document.createElement('div');
        this.#tooltipArrow.id = 'tooltip_arrow';

        this.#tooltip.appendChild(this.#tooltipContainer);
        this.#tooltip.appendChild(this.#tooltipArrow);
        document.body.appendChild(this.#tooltip);
    }

    #destroyTipContainer() {
        this.#tooltip?.remove();
        this.#tooltip = this.#tooltipContainer = this.#tooltipArrow = null;
    }

    #updateContent(el) {
        let text;

        if (this.#includeHtml(el)) {
            text = el.innerHTML;
        } else if (el instanceof HTMLImageElement) {
            text = el.getAttribute('alt');
            text = text?.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/"/g, '&quot;').replace(/'/g, '&apos;');
        } else {
            text = el.getAttribute('title');
            this.#migrateElementTitle(el, true);
            text = text?.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/"/g, '&quot;').replace(/'/g, '&apos;');
        }

        if (!text) {
            return;
        }

        this.#tooltipContainer.innerHTML = text;
    }

    #updatePosition(el) {
        Position.compute(el, this.#tooltip, this.#tooltipArrow);
    }

    #migrateElementTitle(el, setTitle = false) {
        if (!el) {
            return;
        }

        const hasDataTitle = el.hasAttribute('data-original-title');

        if (setTitle && !hasDataTitle) {
            el.setAttribute('data-original-title', el.getAttribute('title'));
            el.removeAttribute('title');
        } else if (hasDataTitle) {
            if (!el.hasAttribute('title')) {
                el.setAttribute('title', el.getAttribute('data-original-title'));
            }

            el.removeAttribute('data-original-title');
        }
    }

    #showTooltip(el, parentAnchor = null, delay = 0) {
        this.#updateContent(el);

        clearTimeout(this.#timer);
        this.#tooltip.style.willChange = 'display,contents';

        this.#timer = setTimeout(() => {
            this.#activeTargets.add(el);
            this.#updatePosition(parentAnchor ?? el);
            this.#tooltip.style.display = 'block';
            this.#tooltip.style.willChange = 'auto';
        }, delay);
    }

    #hideTooltip(el) {
        this.#migrateElementTitle(el);

        clearTimeout(this.#timer);
        this.#activeTargets.delete(el);
        this.#tooltip.style.display = 'none';
    }

    /**
     * Migrate legacy targets to proper controller targets.
     */
    static afterLoad(identifier) {
        const targetSelectors = TooltipsController.elements;

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
