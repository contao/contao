import * as ace from 'ace-builds/src-noconflict/ace';
import * as extCodeLens from 'ace-builds/src-noconflict/ext-code_lens';
import * as extLanguageTools from 'ace-builds/src-noconflict/ext-language_tools';
import * as extWhitespace from 'ace-builds/src-noconflict/ext-whitespace';
import 'ace-builds/src-noconflict/mode-twig';
import themeDark from '!!css-loader!../../styles/twig-editor/contao-twig-dark.pcss';
import themeLight from '!!css-loader!../../styles/twig-editor/contao-twig-light.pcss';
import PhpMode from 'ace-builds/src-noconflict/mode-php';
import ContaoTwigMode from './contao-twig-mode';
import { analyzeBlocks, analyzeReferences } from './token-analyzer';

export class TwigEditor {
    static autocompleteDataByEditorId = new Map();

    constructor(element) {
        const environment = JSON.parse(
            element.closest('[data-twig-environment]').getAttribute('data-twig-environment'),
        );

        this._element = element;
        this._element.classList.add('hidden');
        this.name = this._element.dataset.name;

        // Ace uses lots of HTML elements to display the highlighted source
        // code. We're therefore running it in a shadow DOM, so that we do not
        // trigger outside mutation observers.
        const target = this.#initializeShadowRoot(element);

        const type = target.dataset.type ?? 'html.twig';

        this.editor = ace.edit(target, {
            mode: type === 'php' ? new PhpMode.Mode() : new ContaoTwigMode.Mode(type.slice(0, -5), environment),
            maxLines: 100,
            wrap: true,
            useSoftTabs: false,
            autoScrollEditorIntoView: true,
            readOnly: target.hasAttribute('readonly'),
            enableBasicAutocompletion: true,
            enableLiveAutocompletion: true,
            liveAutocompletionDelay: 300,
            enableKeyboardAccessibility: true,
        });

        this.editor.renderer.attachToShadowRoot();

        this.setColorScheme(document.documentElement.dataset.colorScheme);
        this.editor.container.style.lineHeight = '1.45';

        extWhitespace.detectIndentation(this.editor.getSession());

        // Register commands
        this.editor.commands.addCommand({
            name: 'lens:block-info',
            readOnly: true,
            exec: (editor, args) => {
                this._element.dispatchEvent(
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
                this._element.dispatchEvent(
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
            this.#registerCodeLensProvider();
        });
    }

    #initializeShadowRoot(element) {
        this._host = document.createElement('div');

        element.insertAdjacentElement('afterend', this._host);
        element.classList.add('hidden');

        const shadowRoot = this._host.attachShadow({ mode: 'open' });
        const target = element.cloneNode();

        shadowRoot.appendChild(target);

        return target;
    }

    #registerCodeLensProvider() {
        const shortName = this.#getShortName(this.name);

        extCodeLens.registerCodeLensProvider(this.editor, {
            provideCodeLenses: (session, callback) => {
                if (session.destroyed) {
                    return;
                }

                const payload = [];

                // We currently only support one code lens per line
                const affectedLines = [];

                for (const reference of analyzeReferences(session)) {
                    if (affectedLines.includes(reference.row)) {
                        continue;
                    }

                    // Ignore references to the same group
                    if (this.#getShortName(reference.name) === shortName) {
                        continue;
                    }

                    payload.push({
                        start: { row: reference.row },
                        command: {
                            id: 'lens:follow',
                            title: reference.name,
                            arguments: [reference.name],
                        },
                    });

                    affectedLines.push(reference.row);
                }

                for (const block of analyzeBlocks(session)) {
                    if (affectedLines.includes(block.row)) {
                        continue;
                    }

                    payload.push({
                        start: { row: block.row },
                        command: {
                            id: 'lens:block-info',
                            title: `Block "${block.name}"`,
                            arguments: [block.name],
                        },
                    });

                    affectedLines.push(block.row);
                }

                callback(null, payload);
            },
        });
    }

    #getShortName(fullyQualifiedName) {
        const matches = /^@Contao(?:_[a-zA-Z0-9_-]+)?(?:\/(.*))?$/.exec(fullyQualifiedName);

        return matches[1] ?? false;
    }

    setAnnotationsData(data) {
        // The language tool extension has a global list of completers. We,
        // however, want completions that vary between files and thus also
        // between editor instances. When completions are requested, we
        // therefore resolve them from a static map based on the editor id.
        TwigEditor.autocompleteDataByEditorId.set(this.editor.id, data.autocomplete);

        extLanguageTools.setCompleters([
            extLanguageTools.textCompleter,
            extLanguageTools.keyWordCompleter,
            extLanguageTools.snippetCompleter,
            {
                id: 'contaoTwigCompleter',
                getCompletions: (editor, session, pos, prefix, callback) => {
                    callback(null, TwigEditor.autocompleteDataByEditorId.get(editor.id));
                },
            },
        ]);

        if ('error' in data) {
            this.editor.getSession().setAnnotations([
                {
                    row: data.error.line - 1,
                    column: 0,
                    type: data.error.type || 'error',
                    text: ` ${data.error.message}`,
                },
            ]);
        }

        if ('deprecations' in data) {
            for (const { line, message } of data.deprecations) {
                this.editor.getSession().setAnnotations([
                    {
                        row: line - 1,
                        column: 0,
                        type: 'warning',
                        text: ` ${message}`,
                    },
                ]);
            }
        }
    }

    setColorScheme(mode) {
        const isDark = mode === 'dark';

        this.editor.setTheme({
            isDark,
            cssClass: `contao-twig-${mode}`,
            cssText: isDark ? themeDark : themeLight,
        });
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
        this._element.textContent = this.getContent();

        this.editor.destroy();

        this._element.classList.remove('hidden');
        this._host.remove();
    }
}
