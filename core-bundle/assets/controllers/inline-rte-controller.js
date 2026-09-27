import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    #editors = new WeakMap();
    #initializations = new WeakMap();

    static targets = ['editor'];

    disconnect() {
        for (const el of this.editorTargets) {
            this.#remove(el);
        }
    }

    editorTargetDisconnected(el) {
        this.#remove(el);
    }

    activate({ currentTarget }) {
        const editor = this.#editors.get(currentTarget);

        // Still initializing or already editing
        if (null === editor || this.#isEditing(currentTarget)) {
            return;
        }

        // Offset of the clicked text
        const offset = this.#getCaretOffset(currentTarget);

        // Blur due to the link prevent handling
        document.activeElement?.blur();

        if (editor) {
            editor.show();
            this.#focus(editor, offset);
            return;
        }

        this.#init(currentTarget, offset);
    }

    click(event) {
        if (this.#isEditing(event.currentTarget) || !event.target.closest('a')) {
            return;
        }

        // Prevent hyperlinks opening when not in edit mode
        event.preventDefault();

        if ('pointerdown' === event.type) {
            event.currentTarget.focus();
        }
    }

    navigate(event) {
        const el = event.currentTarget;

        if ('Escape' === event.key) {
            event.preventDefault();
            el.blur();
            return;
        }

        if (!event.key.startsWith('Arrow')) {
            return;
        }

        const caret = this.#getCaretPosition(this.#editors.get(el));

        if (!caret) {
            return;
        }

        // Let table wizard move to the cell
        const detail = { key: event.key, ...caret };

        if (this.dispatch('navigate', { target: el, detail, cancelable: true }).defaultPrevented) {
            event.preventDefault();
        }
    }

    // Updates a cell after its input or textarea has been changed
    update({ target }) {
        const el = target.nextElementSibling;

        if (!this.editorTargets.includes(el)) {
            return;
        }

        const editor = this.#editors.get(el);

        if (!editor) {
            this.#reset(el, target.value);
            return;
        }

        // Do not move the selection as input or textarea might have been updated by a blur of this editor
        if (editor.getContent() !== target.value) {
            editor.setContent(target.value, { no_selection: true });
            editor.setDirty(false);
        }
    }

    #init(el, offset) {
        const config = this.element.inlineRteConfig;

        if (!config) {
            console.error(
                'No TinyMCE config was attached to the DOM element (expando "inlineRteConfig").',
                this.element,
            );
            return;
        }

        // Initialize map
        this.#editors.set(el, null);

        const initialization = tinymce?.init({ ...config, target: el, setup: (editor) => this.#setup(editor) });
        this.#initializations.set(el, initialization);

        initialization.then((editors) => {
            const editor = editors[0] ?? null;

            if (this.#initializations.get(el) !== initialization || !this.element.contains(el)) {
                editor?.remove();
                return;
            }

            this.#initializations.delete(el);

            if (!editor) {
                this.#editors.delete(el);
                return;
            }

            this.#editors.set(el, editor);
            this.#focus(editor, offset);
        });
    }

    #remove(el) {
        const editor = this.#editors.get(el);
        this.#editors.delete(el);
        this.#initializations.delete(el);
        editor?.remove();
    }

    #setup(editor) {
        editor.on('focus', () => window.dispatchEvent(new Event('store-scroll-offset')));

        // Hide instead of removing as reinit during focus event crashes TinyMCE
        editor.on('blur', () => {
            // Keep when TinyMCE dialog is open, the Contao picker blurs the editor
            if (document.querySelector('.tox-dialog')) {
                return;
            }

            this.#commit(editor);
            editor.hide();
        });
    }

    #isEditing(el) {
        const editor = this.#editors.get(el);

        return !!editor && !editor.hidden;
    }

    #getCaretPosition(editor) {
        if (!editor?.selection.isCollapsed()) {
            return null;
        }

        const body = editor.getBody();
        const before = editor.selection.getRng().cloneRange();
        const after = before.cloneRange();
        before.setStart(body, 0);
        after.setEnd(body, body.childNodes.length);

        return { atStart: '' === before.toString().trim(), atEnd: '' === after.toString().trim() };
    }

    #commit(editor) {
        // Only edited cells are written back to make sure TinyMCE does not rewrite unedited cells
        if (!editor.isDirty()) {
            return;
        }

        const textarea = editor.targetElm.previousElementSibling;
        textarea.value = editor.getContent();
        textarea.dispatchEvent(new Event('input', { bubbles: true }));
        editor.setDirty(false);
    }

    // Reset TinyMCE specific content (e.g. cloned or duplicated elements)
    #reset(el, content) {
        for (const name of ['id', 'contenteditable', 'spellcheck']) {
            el.removeAttribute(name);
        }

        for (const name of [...el.classList]) {
            if (name.startsWith('mce-')) {
                el.classList.remove(name);
            }
        }

        el.innerHTML = content;
    }

    #focus(editor, offset) {
        const walker = document.createTreeWalker(editor.getBody(), NodeFilter.SHOW_TEXT);
        let node;

        while ((node = walker.nextNode()) && offset > node.length) {
            offset -= node.length;
        }

        if (node) {
            editor.selection.setCursorLocation(node, offset);
        } else {
            editor.selection.select(editor.getBody(), true);
            editor.selection.collapse(false);
        }

        editor.focus();
    }

    #getCaretOffset(el) {
        const selection = window.getSelection();

        if (!selection?.rangeCount || !el.contains(selection.anchorNode)) {
            return 0;
        }

        const range = document.createRange();
        range.selectNodeContents(el);
        range.setEnd(selection.anchorNode, selection.anchorOffset);

        return range.toString().length;
    }
}
