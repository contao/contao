import { Controller } from "@hotwired/stimulus";

export default class extends Controller {
    connect() {
        this.initialized = new Set();
        this.createTooltip();
        this.setup();
        this.addObserver();
    }

    disconnect() {
        if (this.observer) {
            this.observer.disconnect();
        }

        this.initialized.forEach(el => {
            document.removeEventListener('touchstart', el.globalTouchstartListener);
        })

        this.initialized.clear();
    }

    createTooltip() {
        this.tooltip = document.createElement('div');
        this.tooltip.setAttribute('role', 'tooltip');
        this.tooltip.classList.add('tip');
        this.tooltip.style.position = 'absolute';
        this.tooltip.style.display = 'none';
        this.element.appendChild(this.tooltip);
    }

    setup() {
        this.selectors = {
            'p.tl_tip':                         { x:  0, y: 23, useContent: true },
            '#home':                            { x:  6, y: 42 },
            '#tmenu a[title]':                  { x:  0, y: 42 },
            'a[title][class^="group-"]':        { x: -6, y: 27 },
            'a[title].navigation':              { x: 25, y: 32 },
            'img[title].gimage':                { x: -9, y: 60 },
            'img[title]:not(.gimage)':          { x: -9, y: 30 },
            'a[title].picker-wizard':           { x: -4, y: 30 },
            'button[title].unselectable':       { x: -4, y: 20 },
            'button[title]:not(.unselectable)': { x: -9, y: 30 },
            'a[title]:not(.picker-wizard)':     { x: -9, y: 30 },
            'input[title]':                     { x: -9, y: 30 },
            'time[title]':                      { x: -9, y: 26 },
            'span[title]':                      { x: -9, y: 26 },
        }

        Object.entries(this.selectors).forEach(([selector, options]) => {
            document.querySelectorAll(selector).forEach(el => {
                if (!this.initialized.has(el)) {
                    this.initialized.add(el);
                    this.init(el, options);
                }
            });
        });
    }

    init(el, options) {
        el.addEventListener('mouseenter', () => this.show(el, options));
        el.addEventListener('touchend', () => this.show(el, options, 0));
        el.addEventListener('mouseleave', () => this.hide(el));

        // Close tooltip when touching anywhere else
        document.addEventListener('touchstart', el.globalTouchstartListener = (e) => {
            if (el.contains(e.target)) {
                return;
            }

            this.hide(el, 0);
        });

        const action = el.closest('button, a');

        // Hide tooltip when clicking a button (usually an operation icon in a wizard widget)
        if (action) {
            action.addEventListener('click', () => {
                clearTimeout(this.timer);
                this.tooltip.style.display = 'none';
                this.tooltip.style.willChange = 'auto';
            })
        }
    }

    show (el, options, delay) {
        delay = typeof delay !== 'undefined' ? delay : 1000;

        let text;

        if (options.useContent) {
            text = el.innerHTML;
        } else {
            text = el.getAttribute('title');
            el.setAttribute('data-original-title', text);
            el.removeAttribute('title');
            text = text?.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/"/g, '&quot;').replace(/'/g, '&apos;');
        }

        if (!text) {
            return;
        }

        clearTimeout(this.timer);
        this.tooltip.style.willChange = 'display,contents';

        this.timer = setTimeout(() => {
            const position = el.getBoundingClientRect();
            const rtl = getComputedStyle(el).direction === 'rtl';
            const clientWidth = document.documentElement.clientWidth;

            if ((rtl && position.x < 200) || (!rtl && position.x < (clientWidth - 200))) {
                this.tooltip.style.left = `${(window.scrollX + position.left + options.x)}px`;
                this.tooltip.style.right = 'auto';
                this.tooltip.classList.remove('tip--rtl');
            } else {
                this.tooltip.style.left = 'auto';
                this.tooltip.style.right = `${(clientWidth - window.scrollX - position.right + options.x)}px`;
                this.tooltip.classList.add('tip--rtl');
            }

            this.tooltip.innerHTML = `<div>${text}</div>`;
            this.tooltip.style.top = `${(window.scrollY + position.top + options.y)}px`;
            this.tooltip.style.display = 'block';
            this.tooltip.style.willChange = 'auto';
        }, delay);
    }

    hide (el, delay) {
        delay = typeof delay !== 'undefined' ? delay : 100;

        if (el.hasAttribute('data-original-title')) {
            if (!el.hasAttribute('title')) {
                el.setAttribute('title', el.getAttribute('data-original-title'));
            }

            el.removeAttribute('data-original-title');
        }

        clearTimeout(this.timer);
        this.tooltip.style.willChange = 'auto';

        if (this.tooltip.style.display === 'block') {
            this.tooltip.style.willChange = 'display';
            this.timer = setTimeout(() => {
                this.tooltip.style.display = 'none';
                this.tooltip.style.willChange = 'auto';
            }, delay);
        }
    }

    addObserver () {
        this.observer = new MutationObserver((mutationsList) => {
            mutationsList.forEach((mutation) => {
                if (mutation.type === 'childList' && mutation.addedNodes.length > 0) {
                    this.setup();
                }
            });
        })

        this.observer.observe(this.element, {
            childList: true,
            subtree: true,
        });
    }
}
