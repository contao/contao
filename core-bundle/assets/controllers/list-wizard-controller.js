import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['row'];

    static values = {
        name: String,
        /** @deprecated Deprecated since Contao 6.1, to be removed in Contao 7 */
        isLegacy: Boolean,
    };

    update() {
        if (this.isLegacyValue) {
            return;
        }

        this.#reorder(this.element, this.nameValue, 0);
    }

    copy(event) {
        const row = this.#getRow(event);

        const newRow = row.cloneNode(true);

        row.after(newRow);
        this.#focus(newRow);

        this.update();
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

    #focus(el) {
        if (!el) {
            return false;
        }

        el.querySelector('input')?.focus();

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
