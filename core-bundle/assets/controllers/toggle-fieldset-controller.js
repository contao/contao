import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static values = {
        id: String,
        table: String,
    }

    static classes = ['collapsed'];

    static afterLoad (identifier, application) {
        const addController = (el, id, table) => {
            const fs = el.parentNode;

            fs.dataset.controller = `${fs.dataset.controller || ''} ${identifier}`;
            fs.setAttribute(`data-${identifier}-id-value`, id);
            fs.setAttribute(`data-${identifier}-table-value`, table);
            fs.setAttribute(`data-${identifier}-collapsed-class`, 'collapsed');
            el.setAttribute('tabindex', 0);
            el.setAttribute('data-action', `click->${identifier}#toggle keydown.enter->${identifier}#toggle keydown.space->${identifier}#prevent:prevent keyup.space->${identifier}#toggle:prevent`);
        }

        const migrateLegacy = () => {
            document.querySelectorAll('legend[data-toggle-fieldset]').forEach(function(el) {
                if (window.console) {
                    console.warn(`Using the "data-toggle-fieldset" attribute on fieldset legends is deprecated and will be removed in Contao 6. Apply the "${identifier}" Stimulus controller instead.`);
                }

                const { id, table } = JSON.parse(el.getAttribute('data-toggle-fieldset'));
                addController(el, id, table);
            });

            AjaxRequest.toggleFieldset = (el, id, table) => {
                const fs = el.parentNode;

                // Already clicked, Stimulus controller was added dynamically
                if (application.getControllerForElementAndIdentifier(fs, identifier)) {
                    return;
                }

                if (window.console) {
                    console.warn('Using AjaxRequest.toggleFieldset() is deprecated and will be removed in Contao 6. Apply the Stimulus actions instead.');
                }

                addController(el, id, table);

                // Optimistically wait until Stimulus has registered the new controller
                setTimeout(() => { application.getControllerForElementAndIdentifier(fs, identifier).toggle(); }, 100);
            };
        }

        // Called as soon as registered, so DOM may not have been loaded yet
        if (document.readyState === "loading") {
            document.addEventListener("DOMContentLoaded", migrateLegacy);
        } else {
            migrateLegacy();
        }
    }

    connect () {
        if (this.element.querySelectorAll('label.error, label.mandatory').length) {
            this.element.classList.remove(this.collapsedClass);
        } else if (this.element.classList.contains('hide')) {
            if (window.console) {
                console.warn(`Using class "hide" on a fieldset is deprecated and will be removed in Contao 6. Use class "${this.collapsedClass}" instead.`);
            }

            this.element.classList.add(this.collapsedClass);
        }

        if (this.element.classList.contains(this.collapsedClass)) {
            this.setAriaExpanded(false);
        } else {
            this.setAriaExpanded(true);
        }
    }

    toggle () {
        if (this.element.classList.contains(this.collapsedClass)) {
            this.open();
            this.setAriaExpanded(true);
        } else {
            this.close();
            this.setAriaExpanded(false);
        }
    }

    open () {
        if (!this.element.classList.contains(this.collapsedClass)) {
            return;
        }

        this.element.classList.remove(this.collapsedClass);
        this.storeState(1);
    }

    close () {
        if (this.element.classList.contains(this.collapsedClass)) {
            return;
        }

        const form = this.element.closest('form');
        const input = this.element.querySelectorAll('[required]');

        let collapse = true;
        for (let i = 0; i < input.length; i++) {
            if (!input[i].value) {
                collapse = false;
                break;
            }
        }

        if (!collapse) {
            if (typeof(form.checkValidity) == 'function') {
                form.querySelector('button[type="submit"]').click();
            }
        } else {
            this.element.classList.add(this.collapsedClass);
            this.storeState(0);
        }
    }

    storeState (state) {
        if (!this.hasIdValue || !this.hasTableValue) {
            return;
        }

        fetch(window.location.href, {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: new URLSearchParams({
                action: 'toggleFieldset',
                id: this.idValue,
                table: this.tableValue,
                state: state,
            })
        });
    }

    setAriaExpanded(state) {
        const button = this.element.querySelector('button');

        if (button) {
            button.ariaExpanded = state;
        }
    }
}
