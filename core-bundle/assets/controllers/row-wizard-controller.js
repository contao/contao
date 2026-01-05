import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['body', 'row', 'copy', 'delete'];

    static values = {
        name: String,
        min: Number,
        max: Number,
    };

    connect() {
        this.#updatePermissions();
    }

    rowTargetConnected() {
        this.updateSorting();
    }

    rowTargetDisconnected() {
        this.updateSorting();
    }

    copy(event) {
        if (!this.#copyAllowed()) {
            return;
        }

        const row = this.#getRow(event);
        const previous = row.previousElementSibling;

        // Cause Choices and similar controllers to be disconnected
        row.remove();

        // Wait until Stimulus controllers are disconnected
        queueMicrotask(() => {
            const newRow = row.cloneNode(true);

            // Re-insert the previous and new row
            if (previous) {
                previous.after(row, newRow);
            } else {
                this.bodyTarget.prepend(row, newRow);
            }

            this.#focus(newRow);
            this.#updatePermissions();
        });
    }

    delete(event) {
        if (!this.#deleteAllowed()) {
            return;
        }

        const row = this.#getRow(event);

        if (this.bodyTarget.children.length > 1) {
            this.#focus(row.nextElementSibling) ||
                this.#focus(row.previousElementSibling) ||
                this.#focus(this.bodyTarget);

            row.remove();
        } else {
            this.#resetInputs(row);
            this.#focus(row);
        }

        this.#updatePermissions();
    }

    /**
     * This method is specific to the row wizard being a "module wizard".
     */
    updateModuleWizardLink(event) {
        const row = this.#getRow(event);
        const link = row.querySelector('.module_link');
        const images = row.querySelectorAll('img.module_image');
        const select = event.target;

        const isContentElement = select.value.startsWith('content-');
        const id = isContentElement ? select.value.replace('content-', '') : select.value;

        const href = new URL(link.href);
        href.searchParams.set('table', isContentElement ? 'tl_content' : 'tl_module');
        href.searchParams.set('id', id);
        link.href = href.toString();

        if (id > 0) {
            link.classList.remove('hidden');

            for (const image of images) {
                image.classList.add('hidden');
            }
        } else {
            link.classList.add('hidden');

            for (const image of images) {
                image.classList.remove('hidden');
            }
        }
    }

    updateNesting(i) {
        const name = this.nameValue.replace(/\d+$/, i);

        this.bodyTarget
            .querySelectorAll(`[for^=${this.nameValue}\\[], [name^=${this.nameValue}\\[]`)
            .forEach((el, i) => {
                if (el.name) {
                    el.name = el.name.replace(new RegExp(`^${this.nameValue}\\[`, 'g'), `${name}[`);
                }

                if (el.id) {
                    el.id = el.id.replace(new RegExp(`^${this.nameValue}_`, 'g'), `${name}_`);
                }

                if (el.getAttribute('for')) {
                    el.setAttribute(
                        'for',
                        el.getAttribute('for').replace(new RegExp(`^${this.nameValue}_`, 'g'), `${name}_`),
                    );
                }
            });

        this.element.setAttribute(`data-${this.identifier}-name-value`, name);
        this.updateSorting();
    }

    updateSorting() {
        // Searches for digits with leading underscore or within brackets
        const regexPattern = /(_\d+)|(\[\d+\])/g;

        Array.from(this.bodyTarget.children).forEach((tr, i) => {
            for (const el of tr.querySelectorAll(
                `[for^=${this.nameValue}\\[], [name^=${this.nameValue}\\[], [id*=${this.nameValue}\\[], .selector_container > ul`,
            )) {
                if (el.name) {
                    el.name = el.name.replace(
                        new RegExp(`^${this.nameValue}\[[0-9]+]`, 'g'),
                        `${this.nameValue}[${i}]`,
                    );
                }

                if (el.id) {
                    el.id = el.id.replace(regexPattern, (match) => (match.includes('[') ? `[${i}]` : `_${i}`));
                }

                if (el.getAttribute('for')) {
                    el.setAttribute(
                        'for',
                        el
                            .getAttribute('for')
                            .replace(new RegExp(`^${this.nameValue}_[0-9]+(_|$)`, 'g'), `${this.nameValue}_${i}$1`),
                    );
                }
            }

            const pickerScript = tr.querySelector('.selector_container > script');

            if (pickerScript) {
                const script = document.createElement('script');
                script.textContent = pickerScript.textContent.replace(regexPattern, (match) =>
                    match.includes('[') ? `[${i}]` : `_${i}`,
                );
                pickerScript.parentNode.replaceChild(script, pickerScript);
            }

            for (const el of tr.querySelectorAll(`[data-controller="${this.identifier}"]`)) {
                this.application.getControllerForElementAndIdentifier(el, this.identifier)?.updateNesting(i);
            }
        });
    }

    #getRow(event) {
        return event.target.closest(`*[data-${this.identifier}-target="row"]`);
    }

    #resetInputs(row) {
        for (const input of row.querySelectorAll('input')) {
            input.value = '';
        }

        for (const select of row.querySelectorAll('select')) {
            select.value = select.children[0].value;
        }
    }

    #focus(el) {
        if (!el) {
            return false;
        }

        el.querySelector('input, select:not(.choices__input), .tl_select.choices')?.focus();

        return true;
    }

    #deleteAllowed() {
        return !(this.hasMinValue && this.bodyTarget.children.length === this.minValue);
    }

    #copyAllowed() {
        return !(this.hasMaxValue && this.bodyTarget.children.length === this.maxValue);
    }

    #updatePermissions() {
        if (this.hasMinValue) {
            const enable = this.#deleteAllowed();

            for (const el of this.deleteTargets) {
                if (enable) {
                    el.removeAttribute('disabled');
                } else {
                    el.disabled = true;
                }
            }
        }

        if (this.hasMaxValue) {
            const enable = this.#copyAllowed();

            for (const el of this.copyTargets) {
                if (enable) {
                    el.removeAttribute('disabled');
                } else {
                    el.disabled = true;
                }
            }
        }
    }
}
