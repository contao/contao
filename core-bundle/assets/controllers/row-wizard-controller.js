import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['body', 'rowTemplate', 'row'];

    rowSnapshots = new Map();

    rowTemplateTargetConnected(template) {
        // We need to queue a micro task here, so that Stimulus will fire
        // rowTargetConnected().
        queueMicrotask(() => {
            this._unwrap(template);
        });
    }

    rowTargetConnected() {
        this.updateSorting();
    }

    rowTargetDisconnected(row) {
        this.rowSnapshots.delete(row);
        this.updateSorting();
    }

    copy(event) {
        const row = this._getRow(event);
        const snapshot = this.rowSnapshots.get(row);

        row.insertAdjacentHTML('afterend', snapshot);
        const newRow = row.nextElementSibling;
        this.rowSnapshots.set(newRow, snapshot);

        this._syncInputs(row, newRow);
    }

    delete(event) {
        if (this.bodyTarget.children.length > 1) {
            this._getRow(event).remove();
        } else {
            this._resetInputs(this._getRow(event));
        }
    }

    enable(event) {
        event.target.previousElementSibling.checked ^= 1;
    }

    move(event) {
        const row = this._getRow(event);

        if (event.code === 'ArrowUp' || event.keyCode === 38) {
            event.preventDefault();

            if (row.previousElementSibling) {
                row.previousElementSibling.insertAdjacentElement('beforebegin', row);
            } else {
                this.bodyTarget.insertAdjacentElement('beforeend', row);
            }

            event.target.focus();
        } else if (event.code === 'ArrowDown' || event.keyCode === 40) {
            event.preventDefault();

            if (row.nextElementSibling) {
                row.nextElementSibling.insertAdjacentElement('afterend', row);
            } else {
                this.bodyTarget.insertAdjacentElement('afterbegin', row);
            }

            event.target.focus();
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
                    el.name = el.name.replace(/\[[0-9]+]/g, `[${ i }]`);
                }

                if (el.id) {
                    el.id = el.id.replace(/_[0-9]+(_|$)/g, `_${ i }$1`)
                }

                if (el.getAttribute('for')) {
                    el.setAttribute('for', el.getAttribute('for').replace(/_[0-9]+(_|$)/g, `_${ i }$1`));
                }
            }
        });
    }

    beforeCache() {
        // Restore the original HTML with template tags before Turbo caches the
        // page. They will get unwrapped again at the restored page.
        for (const row of this.rowTargets) {
            this._wrap(row);
        }
    }

    _unwrap(template) {
        this.rowSnapshots.set(
            template.content.querySelector('*[data-contao--row-wizard-target="row"]'),
            template.innerHTML,
        );

        template.replaceWith(template.content);
    }

    _wrap(row) {
        const template = document.createElement('template');
        template.setAttribute('data-contao--row-wizard-target', 'rowTemplate');
        template.innerHTML = this.rowSnapshots.get(row);

        this._syncInputs(row, template.content.querySelector('tr'));

        row.replaceWith(template);
    }

    _getRow(event) {
        return event.target.closest('*[data-contao--row-wizard-target="row"]');
    }

    _syncInputs(rowFrom, rowTo) {
        const selectsFrom = rowFrom.querySelectorAll('input:not(.choices__input--cloned), select');
        const selectsTo = rowTo.querySelectorAll('input, select');

        for (let i = 0; i < selectsFrom.length; i++) {
            selectsTo[i].value = selectsFrom[i].value;
        }
    }

    _resetInputs(row) {
        for (const input of row.querySelectorAll('input')) {
            input.value = '';
        }

        for (const select of row.querySelectorAll('select')) {
            select.value = select.children[0].value;
        }
    }
}
