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
        this.editor = ace.edit(this.container);
        this.editor.getSession().setValue(this.element.value);

        this.editor.on('focus', () => {
            window.dispatchEvent(new Event('store-scroll-offset'));
        });

        this.editor.getSession().on('change', () => {
            this.element.value = this.editor.getValue();
        });

        // Disable command conflicts with AltGr (see #5792)
        this.editor.commands.bindKey('Ctrl-alt-a|Ctrl-alt-e|Ctrl-alt-h|Ctrl-alt-l|Ctrl-alt-s', null);

        // Execute the config callback
        this.element?.configCallback(this.editor);

        this.setMaxLines();
        window.addEventListener('resize', this.setMaxLines.bind(this));
    }

    disconnect() {
        this.editor.destroy();
        this.container.remove();
    }

    setMaxLines() {
        this.editor.setOption('maxLines', Math.floor((window.innerHeight - 320) / Math.floor(12 * this.editor.container.style.lineHeight)));
    }
}
