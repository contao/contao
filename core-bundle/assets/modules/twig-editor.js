export class TwigEditor {
    constructor(element) {
        this.name = element.dataset.name;
        this.resourceUrl = element.dataset.resourceUrl;

        this.editor = ace.edit(element, {
            mode: 'ace/mode/twig',
            maxLines: 100,
            wrap: true,
            useSoftTabs: false,
            autoScrollEditorIntoView: true,
            readOnly: element.hasAttribute('readonly'),
        });

        this.setColorScheme(document.documentElement.dataset.colorScheme);
        this.editor.container.style.lineHeight = '1.45';

        const whitespace = ace.require('ace/ext/whitespace');
        whitespace.detectIndentation(this.editor.getSession());
    }

    setColorScheme(mode) {
        this.editor.setTheme(mode === 'dark' ? 'ace/theme/twilight' : 'ace/theme/clouds');
    }

    isEditable() {
        return !this.editor.getReadOnly();
    }

    getContent() {
        return this.editor.getValue();
    }

    destroy() {
        this.editor.destroy();
    }
}
