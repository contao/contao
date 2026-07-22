import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    #template;

    static targets = ['body', 'row', 'copy', 'delete', 'ghost'];

    static values = {
        name: String,
        min: Number,
        max: Number,
        empty: Boolean,
    };

    connect() {
        this.#template = this.rowTargets[0].cloneNode(true);

        if (this.hasGhostTarget) {
            this.#buildGhostRow();
        }

        if (this.emptyValue) {
            this.rowTargets[0].hidden = true;
        }

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

    add() {
        const firstRow = this.rowTargets[0];

        if (firstRow.hidden) {
            this.#enableRow(firstRow);
            this.#focus(firstRow);
            this.#updatePermissions();
            return;
        }

        const newRow = this.#template.cloneNode(true);

        this.#resetInputs(newRow);
        this.bodyTarget.appendChild(newRow);
        this.#focus(newRow);
        this.#updatePermissions();
    }

    delete(event) {
        if (!this.#deleteAllowed()) {
            return;
        }

        const row = this.#getRow(event);

        if (this.rowTargets.length > 1) {
            this.#focus(row.nextElementSibling) ||
                this.#focus(row.previousElementSibling) ||
                this.#focus(this.bodyTarget);

            row.remove();
        } else {
            this.#resetInputs(row);
            this.#disableRow(row);
            this.#focus(this.bodyTarget);
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
        const selector = this.nameValue.replaceAll('[', '\\[').replaceAll(']', '\\]');
        const name = this.nameValue.replace(/\d+$/, i);

        this.bodyTarget
            .querySelectorAll(`[for^=${selector}\\[],[for^=opt_${selector}\\[],[name^=${selector}\\[]`)
            .forEach((el) => {
                if (el.name) {
                    el.name = el.name.replace(new RegExp(`^${this.nameValue}\\[`, 'g'), `${name}[`);
                }

                if (el.id) {
                    el.id = el.id.replace(new RegExp(`^${this.nameValue}_`, 'g'), `${name}_`);
                }

                if (el.getAttribute('for')) {
                    el.setAttribute(
                        'for',
                        el.getAttribute('for').replace(new RegExp(`${this.nameValue}_`, 'g'), `${name}_`),
                    );
                }
            });

        this.element.setAttribute(`data-${this.identifier}-name-value`, name);
        this.updateSorting();
    }

    updateSorting() {
        const selector = this.nameValue.replaceAll('[', '\\[').replaceAll(']', '\\]');
        const regexPattern = new RegExp(`${this.nameValue.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')}\\[[0-9]+\\]`, 'g');

        Array.from(this.bodyTarget.children).forEach((tr, i) => {
            for (const el of tr.querySelectorAll(
                `[for^=${selector}\\[], [for^=opt_${selector}\\[], [name^=${selector}\\[], [id*=${selector}\\[]`,
            )) {
                if (el.name) {
                    el.name = el.name.replace(regexPattern, `${this.nameValue}[${i}]`);
                }

                if (el.id) {
                    el.id = el.id.replace(regexPattern, `${this.nameValue}[${i}]`);
                }

                if (el.getAttribute('for')) {
                    el.setAttribute('for', el.getAttribute('for').replace(regexPattern, `${this.nameValue}[${i}]`));
                }
            }

            const pickerScript = tr.querySelector('.selector_container > script');

            if (pickerScript) {
                const script = document.createElement('script');
                script.textContent = pickerScript.textContent.replace(regexPattern, `${this.nameValue}[${i}]`);
                pickerScript.parentNode.replaceChild(script, pickerScript);
            }

            for (const el of tr.querySelectorAll(`[data-controller="${this.identifier}"]`)) {
                this.application.getControllerForElementAndIdentifier(el, this.identifier)?.updateNesting(i);
            }
        });

        const optionsRegexPattern = new RegExp(`^${this.nameValue}_(default|group)_(\\d+)$`);

        Array.from(this.bodyTarget.children).forEach((tr, i) => {
            for (const el of tr.querySelectorAll(
                `[for^=${selector}_default_], [for^=${selector}_group_], [id^=${selector}_default_], [id^=${selector}_group_]`,
            )) {
                if (el.id) {
                    el.id = el.id.replace(optionsRegexPattern, `${this.nameValue}_$1_${i}`);
                }

                if (el.getAttribute('for')) {
                    el.setAttribute(
                        'for',
                        el.getAttribute('for').replace(optionsRegexPattern, `${this.nameValue}_$1_${i}`),
                    );
                }
            }
        });
    }

    #buildGhostRow() {
        const last = this.ghostTarget.querySelector('.tl_right');

        for (const cell of this.#template.children) {
            if (cell.classList.contains('tl_right') || cell.querySelector('.drag-handle')) {
                continue;
            }

            const ghostCell = cell.cloneNode(true);

            for (const el of ghostCell.querySelectorAll('[data-controller="contao--row-wizard"]')) {
                el.removeAttribute('data-controller');

                for (const rw of el.querySelectorAll(
                    '.row-wizard-ghost, [data-contao--row-wizard-target="row"]:not(:first-child)',
                )) {
                    rw.remove();
                }
            }

            for (const el of ghostCell.querySelectorAll('input, select, textarea, button, a')) {
                el.disabled = true;
                el.tabindex = -1;
                el.removeAttribute('href');
            }

            this.ghostTarget.insertBefore(ghostCell, last);
        }

        this.#resetInputs(this.ghostTarget);
    }

    #getRow(event) {
        return event.target.closest(`*[data-${this.identifier}-target="row"]`);
    }

    #disableRow(row) {
        if (!this.hasGhostTarget) {
            return;
        }

        row.querySelector(`input[name="${this.nameValue}[_rows][]"]`).disabled = true;
        row.hidden = true;
    }

    #enableRow(row) {
        if (!this.hasGhostTarget) {
            return;
        }

        row.hidden = false;
        row.querySelector(`input[name="${this.nameValue}[_rows][]"]`).disabled = false;
    }

    #resetInputs(row) {
        for (const input of row.querySelectorAll('input:not([type="checkbox"], [type="radio"], [name$="[_rows][]"])')) {
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
        return this.hasDeleteTarget && (!this.hasMinValue || this.rowTargets.length > this.minValue);
    }

    #copyAllowed() {
        return this.hasCopyTarget && (!this.hasMaxValue || this.rowTargets.length < this.maxValue);
    }

    #updatePermissions() {
        this.element.dataset.rowsCount = this.rowTargets.filter((row) => !row.hidden).length;

        const canCopy = this.#copyAllowed();
        const canDelete = this.#deleteAllowed();

        for (const el of this.deleteTargets) {
            el.disabled = canDelete ? '' : true;
        }

        for (const el of this.copyTargets) {
            el.disabled = canCopy ? '' : true;
        }

        this.ghostTarget.hidden = !canCopy;
    }
}
