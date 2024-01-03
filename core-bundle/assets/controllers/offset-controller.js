import { Controller } from '@hotwired/stimulus'

export default class extends Controller {
    static targets = ['scrollTo'];

    static values = {
        sessionKey: {
            type: String,
            default: 'contao_backend_offset'
        },
        addToAttribute: {
            type: String,
            default: 'data-add-to-scroll-offset'
        },
        behavior: {
            type: String,
            default: 'instant'
        },
    };

    // Backwards compatibility: automatically register the Stimulus controller if the legacy methods are used
    static afterLoad(identifier, application) {
        const loadFallback = () => {
            return new Promise((resolve, reject) => {
                const controller = application.getControllerForElementAndIdentifier(document.documentElement, identifier);

                if (controller) {
                    resolve(controller);
                    return;
                }

                const { controllerAttribute } = application.schema;
                document.documentElement.setAttribute(controllerAttribute, `${document.documentElement.getAttribute(controllerAttribute) || ''} ${ identifier }`);

                setTimeout(() => {
                    const controller = application.getControllerForElementAndIdentifier(document.documentElement, identifier);
                    controller && resolve(controller) || reject(controller);
                }, 100);
            });
        }

        if (window.Backend && !window.Backend.initScrollOffset) {
            window.Backend.initScrollOffset = () => {
                console.warn('Backend.initScrollOffset() is deprecated. Please use the Stimulus controller instead.');
                loadFallback();
            }
        }

        if (window.Backend && !window.Backend.getScrollOffset) {
            window.Backend.getScrollOffset = () => {
                console.warn('Backend.getScrollOffset() is deprecated. Please use the Stimulus controller instead.');
                loadFallback().then((controller) => controller.discard());
            }
        }
    }

    initialize () {
        this.store = this.store.bind(this);
    }

    connect () {
        if (this.offset) {
            this.element.scrollTo({
                top: this.offset + this.additionalOffset,
                behavior: this.behaviorValue
            });

            this.offset = null;
        }

        this.buttons = document.querySelectorAll('.tl_submit_container button[name]:not([name="save"])');

        this.buttons.forEach((button) => {
            button.addEventListener('click', this.store, { passive: true });
        });
    }

    disconnect () {
        if (this.buttons) {
            this.buttons.forEach((button) => {
                button.removeEventListener('click', this.store);
            });

            this.buttons = null;
        }
    }

    scrollToTargetConnected() {
        this.scrollToTarget.scrollIntoView({
            behavior: this.behaviorValue
        });
    }

    store () {
        this.offset = this.element.scrollTop;
    }

    discard () {
        this.offset = null;
    }

    get offset () {
        const value = window.sessionStorage.getItem(this.sessionKeyValue);

        return value ? parseInt(value) : null;
    }

    set offset (value) {
        if (value === null || value === undefined) {
            window.sessionStorage.removeItem(this.sessionKeyValue);
            return;
        }

        window.sessionStorage.setItem(this.sessionKeyValue, String(value))
    }

    get additionalOffset () {
        let additionalOffset = 0;

        document.querySelectorAll(`[${this.addToAttributeValue}]`).forEach((el) => {
            let offset = el.getAttribute(this.addToAttributeValue),
                scrollSize = el.scrollTop,
                negative = false,
                percent = false;

            // No specific offset desired, take scrollSize
            if (!offset) {
                additionalOffset += scrollSize;
                return;
            }

            // Negative
            if (offset.charAt(0) === '-') {
                negative = true;
                offset = offset.substring(1);
            }

            // Percent
            if (offset.charAt(offset.length - 1) === '%') {
                percent = true;
                offset = offset.substring(0, offset.length - 1);
            }

            offset = parseInt(offset, 10);

            if (percent) {
                offset = Math.round(scrollSize * offset / 100);
            }

            if (negative) {
                offset = offset * -1;
            }

            additionalOffset += offset;
        });

        return additionalOffset;
    }
}
