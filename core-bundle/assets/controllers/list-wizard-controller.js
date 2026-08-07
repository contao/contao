import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['row'];

    static values = {
        name: String,
        allowNesting: Boolean,
    };

    update() {
        if (!this.allowNestingValue) {
            return;
        }

        this.#reorder(this.element, this.nameValue, 0);
    }

    copy(event) {
        this.#addRow(this.#getRow(event), false);
    }

    delete(event) {
        const row = this.#getRow(event);
        const sublist = row.querySelector(':scope > ul');

        // Move child list instead of deleting fully
        if (sublist?.children.length > 0) {
            row.after(...sublist.children);
        }

        if (this.rowTargets.length > 1) {
            this.#focus(row.nextElementSibling) || this.#focus(row.previousElementSibling);

            row.remove();
        } else {
            row.querySelector(':scope > input').value = '';
        }

        this.update();
    }

    navigate(event) {
        const input = event.target;
        const row = this.#getRow(event);
        const rows = this.rowTargets;

        const i = rows.indexOf(row);
        const atStart = 0 === input.selectionStart;
        const atEnd = input.value.length === input.selectionStart;

        if (this.allowNestingValue && 'ArrowLeft' === event.key && atStart) {
            this.outdent(event);
            input.focus();
        } else if (this.allowNestingValue && 'ArrowRight' === event.key && atEnd) {
            this.indent(event);
            input.focus();
        } else if ('ArrowUp' === event.key && atStart) {
            this.#focus(rows[i - 1]);
        } else if ('ArrowDown' === event.key && atEnd) {
            this.#focus(rows[i + 1]);
        } else if ('Enter' === event.key) {
            this.#addRow(row, true);
            event.preventDefault();
        }
    }

    indent(event) {
        const row = this.#getRow(event);
        const previous = row.previousElementSibling;

        if (!previous) {
            return;
        }

        previous.querySelector(':scope > ul').append(row);
        this.update();
    }

    outdent(event) {
        const row = this.#getRow(event);
        const parent = row.parentElement.closest('li');

        if (!parent) {
            return;
        }

        // Outdent every child
        while (row.nextElementSibling) {
            row.querySelector(':scope > ul').append(row.nextElementSibling);
        }

        parent.after(row);

        this.update();
    }

    #addRow(row, reset = true) {
        const newRow = row.cloneNode(true);

        if (reset) {
            for (const input of newRow.querySelectorAll('input')) {
                input.value = '';
            }

            newRow.querySelector(':scope > ul')?.replaceChildren();
        }

        row.after(newRow);
        this.#focus(newRow);
        this.update();
    }

    #focus(el) {
        if (!el) {
            return false;
        }

        el.querySelector(':scope > input')?.focus();

        return true;
    }

    #reorder(list, prefix, depth) {
        for (const [i, row] of list.querySelectorAll(':scope > li').entries()) {
            const name = `${prefix}[${i}]`;

            row.querySelector(':scope > input').name = `${name}[item]`;
            row.querySelector(':scope > .indent').disabled = i === 0;
            row.querySelector(':scope > .outdent').disabled = depth === 0;

            this.#reorder(row.querySelector(':scope > ul'), `${name}[list]`, depth + 1);
        }
    }

    #getRow(event) {
        return event.target.closest('li');
    }
}
