import * as ace from 'ace-builds/src-noconflict/ace';
import * as extCodeLens from 'ace-builds/src-noconflict/ext-code_lens';
import * as extWhitespace from 'ace-builds/src-noconflict/ext-whitespace';
import * as themeLight from 'ace-builds/src-noconflict/theme-clouds';
import * as themeDark from 'ace-builds/src-noconflict/theme-twilight';
import 'ace-builds/src-noconflict/ext-language_tools';
import 'ace-builds/src-noconflict/mode-twig';

export class TwigEditor {
    constructor(element) {
        this.containerBackup = element.cloneNode();
        this.name = element.dataset.name;

        const environment = JSON.parse(
            element.closest('[data-twig-environment]').getAttribute('data-twig-environment'),
        );

        this.editor = ace.edit(element, {
            mode: new (this.#getMode(environment))(),
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

        extWhitespace.detectIndentation(this.editor.getSession());

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

    #getMode(environment) {
        const oop = ace.require('ace/lib/oop');
        const TwigMode = ace.require('ace/mode/twig').Mode;
        const HtmlHighlightRules = ace.require('ace/mode/html_highlight_rules').HtmlHighlightRules;
        const MatchingBraceOutdent = ace.require('ace/mode/matching_brace_outdent').MatchingBraceOutdent;

        const ContaoTwigHighlightRules = function () {
            HtmlHighlightRules.call(this);

            let tags = environment.tags.join('|');
            tags = `${tags}|end${tags.replace(/\|/g, '|end')}`;

            const keywordMapper = this.createKeywordMapper(
                {
                    'support.function.twig': [
                        ...environment.filters,
                        ...environment.functions,
                        ...environment.tests,
                    ].join('|'),
                    'keyword.control.twig': tags,
                    'keyword.operator.twig': 'b-and|b-xor|b-or|in|is|and|or|not',
                    'constant.language.twig': 'null|none|true|false',
                },
                'identifier',
            );

            for (const rule in this.$rules) {
                this.$rules[rule].unshift(
                    {
                        token: 'variable.other.readwrite.local.twig',
                        regex: '\\{\\{-?',
                        push: 'twig-start',
                    },
                    {
                        token: 'meta.tag.twig',
                        regex: '\\{%-?',
                        push: 'twig-start',
                    },
                    {
                        token: 'comment.block.twig',
                        regex: '\\{#-?',
                        push: 'twig-comment',
                    },
                );
            }
            this.$rules['twig-comment'] = [
                {
                    token: 'comment.block.twig',
                    regex: '.*-?#\\}',
                    next: 'pop',
                },
            ];
            this.$rules['twig-start'] = [
                {
                    token: 'variable.other.readwrite.local.twig',
                    regex: '-?\\}\\}',
                    next: 'pop',
                },
                {
                    token: 'meta.tag.twig',
                    regex: '-?%\\}',
                    next: 'pop',
                },
                {
                    token: 'string',
                    regex: "'",
                    next: 'twig-qstring',
                },
                {
                    token: 'string',
                    regex: '"',
                    next: 'twig-qqstring',
                },
                {
                    token: 'constant.numeric', // hex
                    regex: '0[xX][0-9a-fA-F]+\\b',
                },
                {
                    token: 'constant.numeric', // float
                    regex: '[+-]?\\d+(?:(?:\\.\\d*)?(?:[eE][+-]?\\d+)?)?\\b',
                },
                {
                    token: 'constant.language.boolean',
                    regex: '(?:true|false)\\b',
                },
                {
                    token: keywordMapper,
                    regex: '[a-zA-Z_$][a-zA-Z0-9_$]*\\b',
                },
                {
                    token: 'keyword.operator.assignment',
                    regex: '=|~',
                },
                {
                    token: 'keyword.operator.comparison',
                    regex: '==|!=|<|>|>=|<=|===',
                },
                {
                    token: 'keyword.operator.arithmetic',
                    regex: '\\+|-|/|%|//|\\*|\\*\\*',
                },
                {
                    token: 'keyword.operator.other',
                    regex: '\\.\\.|\\|',
                },
                {
                    token: 'punctuation.operator',
                    regex: /[?:,;.]/,
                },
                {
                    token: 'paren.lparen',
                    regex: /[\[({]/,
                },
                {
                    token: 'paren.rparen',
                    regex: /[\])}]/,
                },
                {
                    token: 'text',
                    regex: '\\s+',
                },
            ];
            this.$rules['twig-qqstring'] = [
                {
                    token: 'constant.language.escape',
                    regex: /\\[\\"$#ntr]|#{[^"}]*}/,
                },
                {
                    token: 'string',
                    regex: '"',
                    next: 'twig-start',
                },
                {
                    defaultToken: 'string',
                },
            ];
            this.$rules['twig-qstring'] = [
                {
                    token: 'constant.language.escape',
                    regex: /\\[\\'ntr]}/,
                },
                {
                    token: 'string',
                    regex: "'",
                    next: 'twig-start',
                },
                {
                    defaultToken: 'string',
                },
            ];

            this.normalizeRules();
        };

        oop.inherits(ContaoTwigHighlightRules, HtmlHighlightRules);

        const Mode = function () {
            this.HighlightRules = ContaoTwigHighlightRules;
            this.$outdent = new MatchingBraceOutdent();
        };
        oop.inherits(Mode, TwigMode);

        (function () {
            this.$id = 'ace/mode/contao-twig';
        }).call(Mode.prototype);

        return Mode;
    }

    registerCodeLensProvider() {
        extCodeLens.registerCodeLensProvider(this.editor, {
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
        this.editor.setTheme(mode === 'dark' ? themeDark : themeLight);
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
