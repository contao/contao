export class TwigEditor {
    constructor(element) {
        this.containerBackup = element.cloneNode();
        this.name = element.dataset.name;
        this.resourceUrl = element.dataset.resourceUrl;

        this.editor = ace.edit(element, {
            mode: 'ace/mode/twig',
            maxLines: 100,
            wrap: true,
            useSoftTabs: false,
            autoScrollEditorIntoView: true,
            readOnly: element.hasAttribute('readonly'),
            enableLiveAutocompletion: true,
            enableKeyboardAccessibility: true,
        });

        this.setColorScheme(document.documentElement.dataset.colorScheme);
        this.editor.container.style.lineHeight = '1.45';

        const whitespace = ace.require('ace/ext/whitespace');
        whitespace.detectIndentation(this.editor.getSession());

        // Register commands
        this.editor.commands.addCommand({
            name: 'lens:block-info',
            readOnly: true,
            exec: (editor, args) => {
                editor.container.dispatchEvent(
                    new CustomEvent('twig-editor:lens:block-info', {
                        bubbles: true,
                        detail: {
                            name: this.name,
                            block: args[0],
                        },
                    }),
                );
            },
        });

        this.editor.commands.addCommand({
            name: 'lens:follow',
            readOnly: true,
            exec: (editor, args) => {
                editor.container.dispatchEvent(
                    new CustomEvent('twig-editor:lens:follow', {
                        bubbles: true,
                        detail: {
                            name: args[0],
                        },
                    }),
                );
            },
        });

        // Setup code lenses
        this.editor.getSession().once('tokenizerUpdate', () => {
            this.registerCodeLensProvider();
        });
    }

    registerCodeLensProvider() {
        const codeLens = ace.require('ace/ext/code_lens');

        codeLens.registerCodeLensProvider(this.editor, {
            provideCodeLenses: (session, callback) => {
                if (session.destroyed) {
                    return;
                }

                const payload = [];

                for (const reference of this.analyzeReferences()) {
                    payload.push({
                        start: { row: reference.row, column: reference.column },
                        command: {
                            id: 'lens:follow',
                            title: reference.name,
                            arguments: [reference.name],
                        },
                    });
                }

                for (const block of this.analyzeBlocks()) {
                    payload.push({
                        start: { row: block.row, column: block.column },
                        command: {
                            id: 'lens:block-info',
                            title: `Block "${block.name}"`,
                            arguments: [block.name],
                        },
                    });
                }

                callback(null, payload);
            },
        });
    }

    analyzeReferences() {
        const references = [];

        for (let row = 0; row < this.editor.getSession().getLength(); row++) {
            const tokens = this.editor.getSession().getTokens(row);

            for (let i = 0; i < tokens.length; i++) {
                if (
                    tokens[i].type === 'meta.tag.twig' &&
                    /^{%-?$/.test(tokens[i].value) &&
                    tokens[i + 2]?.type === 'keyword.control.twig' &&
                    ['extends', 'use'].includes(tokens[i + 2].value) &&
                    tokens[i + 4]?.type === 'string'
                ) {
                    const name = tokens[i + 4].value.replace(/["']/g, '');

                    if (/^@Contao(_.+)?\//.test(name)) {
                        references.push({ name, row, column: tokens[i].start });
                    }
                }
            }
        }

        return references;
    }

    analyzeBlocks() {
        const blocks = [];

        for (let row = 0; row < this.editor.getSession().getLength(); row++) {
            const tokens = this.editor.getSession().getTokens(row);

            for (let i = 0; i < tokens.length; i++) {
                if (
                    tokens[i].type === 'meta.tag.twig' &&
                    /^{%-?$/.test(tokens[i].value) &&
                    tokens[i + 2]?.type === 'keyword.control.twig' &&
                    tokens[i + 2].value === 'block' &&
                    tokens[i + 4]?.type === 'identifier'
                ) {
                    blocks.push({ name: tokens[i + 4].value, row, column: tokens[i].start });
                }
            }
        }

        return blocks;
    }

    setAnnotationsData(data) {
        this.editor.completers = [
            {
                getCompletions: (editor, session, pos, prefix, callback) => {
                    callback(null, data.autocomplete);
                },
            },
        ];

        if ('error' in data) {
            this.editor.getSession().setAnnotations([
                {
                    row: data.error.line - 1,
                    type: data.error.type || 'error',
                    text: ` ${data.error.message}`,
                },
            ]);
        }
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

    focus() {
        this.editor.focus();
    }

    destroy() {
        // Destroying the ACE instance does not fully reset the HTML, so we
        // manually restore the container by using the cloned backup with
        // updated content.
        this.containerBackup.textContent = this.getContent();
        this.editor.container.replaceWith(this.containerBackup);

        this.editor.destroy();
    }
}
