import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['body', 'row'];

    rowTargetConnected() {
        this.updateSorting();
    }

    rowTargetDisconnected() {
        this.updateSorting();
    }

    copy(event) {
        const row = this._getRow(event);
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

            this._focus(newRow);
        });
    }

    delete(event) {
        const row = this._getRow(event);

        if (this.bodyTarget.children.length > 1) {
            this._focus(row.nextElementSibling) ||
                this._focus(row.previousElementSibling) ||
                this._focus(this.bodyTarget);
            row.remove();
        } else {
            this._resetInputs(row);
            this._focus(row);
        }
    }

    /**
     * This method is specific to the row wizard being a "module wizard".
     */
    updateModuleWizardLink(event) {
        const row = this._getRow(event);
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

    updateSorting() {
        Array.from(this.bodyTarget.children).forEach((tr, i) => {
            for (const el of tr.querySelectorAll('label, input, select')) {
                if (el.name) {
                    el.name = el.name.replace(/\[[0-9]+]/g, `[${i}]`);
                }

                if (el.id) {
                    el.id = el.id.replace(/_[0-9]+(_|$)/g, `_${i}$1`);
                }

                if (el.getAttribute('for')) {
                    el.setAttribute('for', el.getAttribute('for').replace(/_[0-9]+(_|$)/g, `_${i}$1`));
                }
            }
        });
    }

    _getRow(event) {
        return event.target.closest('*[data-contao--row-wizard-target="row"]');
    }

    _resetInputs(row) {
        for (const input of row.querySelectorAll('input')) {
            input.value = '';
        }

        for (const select of row.querySelectorAll('select')) {
            select.value = select.children[0].value;
        }
    }

    _focus(el) {
        if (!el) {
            return false;
        }

        el.querySelector('input, select:not(.choices__input), .tl_select.choices')?.focus();

        return true;
    }
}
