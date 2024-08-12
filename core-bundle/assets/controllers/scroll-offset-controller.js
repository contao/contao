import { Controller } from '@hotwired/stimulus'

export default class extends Controller {
    static targets = ['scrollTo', 'autoFocus', 'widgetError'];

    static values = {
        sessionKey: {
            type: String,
            default: 'contao_backend_offset'
        },
        behavior: {
            type: String,
            default: 'instant'
        },
        block: {
            type: String,
            default: 'center'
        }
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
        if (!this.offset) return;

        window.scrollTo({
            top: this.offset,
            behavior: this.behaviorValue,
            block: this.blockValue
        });

        this.offset = null;
    }

    scrollToTargetConnected() {
        this.scrollToTarget.scrollIntoView({
            behavior: this.behaviorValue,
            block: this.blockValue
        });
    }

    autoFocusTargetConnected() {
        if (this.offset || this.autoFocus) return;

        const input = this.autoFocusTarget;

        if (
            input.disabled || input.readonly
            || !input.offsetWidth || !input.offsetHeight
            || input.closest('.chzn-search')
            || input.autocomplete && input.autocomplete !== 'off'
        ) {
            return;
        }

        this.autoFocus = true;
        input.focus();
    }

    widgetErrorTargetConnected() {
        this.widgetErrorTarget.scrollIntoView({
            behavior: 'smooth',
            block: 'start'
        });
    }

    store () {
        this.offset = this.element.scrollTop;
    }

    discard () {
        this.offset = null;
    }

    scrollToWidgetError() {
        if (this.hasWidgetErrorTarget) {
            this.widgetErrorTargetConnected();
        }
    }

    get offset () {
        const value = window.sessionStorage.getItem(this.sessionKeyValue);

        return value ? parseInt(value) : null;
    }

    set offset (value) {
        if (value === null || value === undefined) {
            window.sessionStorage.removeItem(this.sessionKeyValue);
        } else {
            window.sessionStorage.setItem(this.sessionKeyValue, String(value));
        }
    }
}
