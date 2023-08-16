import { Controller } from '@hotwired/stimulus';

let activeElements = 0;
let globalOperation;

export default class extends Controller {
    static values = {
        max: {
            type: Number,
            default: 112,
        },
    }

    /**
     * Automatically register controller on all legacy ".limit_height" classes
     */
    static afterLoad(identifier, application) {
        const updateLegacy = () => {
            document.querySelectorAll('div.limit_height').forEach(function(div) {
                const parent = div.parentNode.closest('.tl_content');

                // Return if the element is a wrapper
                if (parent && (parent.classList.contains('wrapper_start') || parent.classList.contains('wrapper_stop'))) return;

                const hgt = Number(div.className.replace(/[^0-9]*/, ''))

                // Return if there is no height value
                if (!hgt) return;

                div.setAttribute(`data-${identifier}-max-value`, hgt);
                div.setAttribute(`data-${identifier}-expand-value`, Contao.lang.expand);
                div.setAttribute(`data-${identifier}-collapse-value`, Contao.lang.collapse);
                div.setAttribute(application.schema.controllerAttribute, identifier);
            });
        }

        // called as soon as registered so DOM may not have loaded yet
        if (document.readyState === "loading") {
            document.addEventListener("DOMContentLoaded", updateLegacy)
        } else {
            updateLegacy()
        }
    }

    connect () {
        const style = window.getComputedStyle(this.element, null);
        const padding = parseFloat(style.paddingTop) + parseFloat(style.paddingBottom);
        this.height = this.element.clientHeight - padding;

        // Do not add the toggle if the preview height is below the max-height
        if (this.height <= this.maxValue) {
            return;
        }

        // Resize the element if it is higher than the maximum height
        this.element.style.height = `${this.maxValue}px`;

        this.button = document.createElement('button');
        this.button.setAttribute('type', 'button');
        this.button.title = 'Expand node';
        this.button.innerHTML = '<span>...</span>';
        this.button.classList.add('unselectable');

        this.button.addEventListener('click', this.toggle.bind(this));

        this.expandHandler = this.expand.bind(this);
        this.collapseHandler = this.collapse.bind(this);
        window.addEventListener(`${this.identifier}.open`, this.expandHandler)
        window.addEventListener(`${this.identifier}.close`, this.collapseHandler)

        this.toggler = document.createElement('div');
        this.toggler.classList.add('limit_toggler');
        this.toggler.append(this.button);

        this.element.append(this.toggler);

        this.addGlobalOperation();
    }

    disconnect () {
        if (this.button) {
            this.button.remove()
        }

        if (this.toggler) {
            this.toggler.remove()
        }

        if (this.expandHandler) {
            window.removeEventListener(`${this.identifier}.open`, this.expandHandler)
        }

        if (this.collapseHandler) {
            window.removeEventListener(`${this.identifier}.close`, this.collapseHandler)
        }
    }

    toggle () {
        if (this.element.style.height === 'auto') {
            this.collapse()
        } else {
            this.expand()
        }
    }

    expand () {
        this.element.style.height = 'auto';
        this.button.title = 'Collapse node';
    }

    collapse () {
        this.element.style.height = `${this.maxValue}px`;
        this.button.title = 'Expand node';
    }

    addGlobalOperation () {
        activeElements++;

        if (globalOperation) {
            return;
        }

        const buttons = document.getElementById('tl_buttons');
        const back = buttons.querySelector('.header_back');
        let open = false;

        globalOperation = document.createElement('button');
        globalOperation.classList = 'header_toggle';
        globalOperation.innerText = 'Expand elements';
        globalOperation.title = 'Expand all elements';
        globalOperation.addEventListener('click', () => {
            if (open) {
                window.dispatchEvent(new CustomEvent(`${this.identifier}.close`));
                globalOperation.innerText = 'Expand elements';
                globalOperation.title = 'Expand all elements';
                open = false;
            } else {
                window.dispatchEvent(new CustomEvent(`${this.identifier}.open`));
                globalOperation.innerText = 'Collapse elements';
                globalOperation.title = 'Collapse all elements';
                open = true;
            }
        });

        back ? back.after(globalOperation) : buttons.prepend(globalOperation);
    }

    removeGlobalOperation () {
        activeElements--;

        if (activeElements === 0 && globalOperation) {
            globalOperation.remove()
        }
    }
}
