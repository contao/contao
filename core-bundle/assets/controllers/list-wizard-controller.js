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
            this.#focus(row.nextElementSibling) ||
                this.#focus(row.previousElementSibling) ||
                this.#focus(row.parentElement.closest('li'));

            row.remove();
        } else {
            this.#setValue(row.querySelector(':scope > input'), '');
        }

        this.update();
    }

    navigate(event) {
        const row = this.#getRow(event);

        if ('Enter' === event.key) {
            // Do not add an item when Enter only confirms a word typed with an input method, and
            // keep Shift+Enter as a line break in the inline RTE
            if (!event.isComposing && !(event.shiftKey && this.#isEditor(event.target))) {
                this.#addRow(row, true);
                event.preventDefault();
            }

            return;
        }

        // The inline RTE dispatches the arrow keys including the caret position
        const caret = event.detail?.key ? event.detail : this.#getCaretPosition(event);

        if (!caret) {
            return;
        }

        const rows = this.rowTargets;
        const i = rows.indexOf(row);

        if (this.allowNestingValue && 'ArrowLeft' === caret.key && caret.atStart) {
            this.outdent(event);
            this.#focus(row);
        } else if (this.allowNestingValue && 'ArrowRight' === caret.key && caret.atEnd) {
            this.indent(event);
            this.#focus(row, true);
        } else if ('ArrowUp' === caret.key && caret.atStart) {
            this.#focus(rows[i - 1]);
        } else if ('ArrowDown' === caret.key && caret.atEnd) {
            this.#focus(rows[i + 1]);
        } else {
            return;
        }

        event.preventDefault();
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
            newRow.querySelector(':scope > ul')?.replaceChildren();
        }

        row.after(newRow);

        // Update the values after inserting the row, so the inline RTE resets the copied editors
        for (const input of newRow.querySelectorAll('input')) {
            this.#setValue(input, reset ? '' : input.value);
        }

        this.#focus(newRow);
        this.update();
    }

    #focus(row, atEnd = false) {
        if (!row) {
            return false;
        }

        const editor = row.querySelector(':scope > .inline-rte');

        if (!editor) {
            row.querySelector(':scope > input')?.focus();

            return true;
        }

        queueMicrotask(() => {
            window.getSelection().collapse(editor, atEnd ? editor.childNodes.length : 0);
            editor.focus();
        });

        return true;
    }

    #setValue(input, value) {
        input.value = value;
        input.dispatchEvent(new Event('input', { bubbles: true }));
    }

    #getCaretPosition({ target, key, shiftKey, altKey, ctrlKey, metaKey }) {
        // Leave selections and shortcuts to the browser
        if (
            !(target instanceof HTMLInputElement) ||
            shiftKey ||
            altKey ||
            ctrlKey ||
            metaKey ||
            target.selectionStart !== target.selectionEnd
        ) {
            return null;
        }

        return { key, atStart: 0 === target.selectionStart, atEnd: target.value.length === target.selectionStart };
    }

    #isEditor(el) {
        return el.classList.contains('inline-rte');
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
