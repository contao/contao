import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static values = {
        type: String,
        readOnly: Boolean,
    }

    connect() {
        // Create a div to apply the editor to
        this.container = document.createElement('div');
        this.container.id = this.element.id + '_div';
        this.container.className = this.element.className;
        this.element.parentNode.insertBefore(this.container, this.element.nextSibling);

        // Hide the textarea
        this.element.style['display'] = 'none';

        // Instantiate the editor
        this.editor = ace.edit(this.container, {
            maxLines: Infinity,
            theme: document.documentElement.dataset.colorScheme === 'dark' ? 'ace/theme/twilight' : 'ace/theme/clouds',
            autoScrollEditorIntoView: true,
            readOnly: this.readOnlyValue,
        });

        this.editor.$blockScrolling = Infinity;
        this.editor.renderer.setScrollMargin(3, 3, 0, 0);
        this.editor.renderer.scrollBy(0, -6);
        this.editor.container.style.lineHeight = 1.45;
        this.editor.getSession().setValue(this.element.value);
        this.editor.getSession().setMode(`ace/mode/${this.typeValue}`);
        this.editor.getSession().setUseSoftTabs(false);
        this.editor.getSession().setUseWrapMode(true);

        // Auto-detect the indentation
        const whitespace = ace.require('ace/ext/whitespace');
        whitespace.detectIndentation(this.editor.getSession());

        // Add the fullscreen command
        this.editor.commands.addCommand({
            name: 'Fullscreen',
            bindKey: 'F11',
            exec: function (editor) {
                const dom = ace.require('ace/lib/dom');
                dom.toggleCssClass(document.body, 'ace-fullsize');
                editor.resize();
            }
        });

        // Enable code autocompletion
        this.editor.setOptions({
            enableSnippets: true,
            enableBasicAutocompletion: true,
            enableLiveAutocompletion: true
        });

        this.editor.on('focus', () => {
            window.dispatchEvent(new Event('store-scroll-offset'));
        });

        this.editor.getSession().on('change', () => {
            this.element.value = this.editor.getValue();
        });

        // Disable command conflicts with AltGr (see #5792)
        this.editor.commands.bindKey('Ctrl-alt-a|Ctrl-alt-e|Ctrl-alt-h|Ctrl-alt-l|Ctrl-alt-s', null);
    }

    disconnect() {
        this.editor.destroy();
        this.container.remove();
    }
}
