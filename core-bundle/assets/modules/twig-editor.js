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

            // add rules to enter Twig block delimiters to all existing HTML rules
            let tag_token_index = 0;

            for (const rule in this.$rules) {
                this.$rules[rule].unshift(
                    {
                        token: 'meta.tag.twig-output-start',
                        regex: /\{\{[-~]?/,
                        push: 'twig-output',
                    },
                    {
                        token: () => {
                            tag_token_index = 0;
                            return 'meta.tag.twig-tag-start';
                        },
                        regex: /\{%[-~]?/,
                        push: 'twig-tag',
                    },
                    {
                        token: 'comment.block.twig-comment-start',
                        regex: /\{#/,
                        push: 'twig-comment',
                    },
                );
            }

            // {{ … }}
            this.$rules['twig-output'] = [
                {
                    token: 'meta.tag.twig-output-end',
                    regex: /[-~]?}}/,
                    next: 'pop',
                },
            ];

            // {% … %}
            this.$rules['twig-tag'] = [
                {
                    token: () => {
                        tag_token_index++;
                        return tag_token_index === 1 ? 'constant.twig-tag-name' : 'text';
                    },
                    regex: `(${environment.tags.join('|')})`,
                },
                {
                    token: 'meta.tag.twig-tag-end',
                    regex: /[-~]?%}/,
                    next: 'pop',
                },
                {
                    token: ['variable', '', 'keyword.operator.assignment'],
                    regex: /(\w+)(\s*)(=)/,
                },
                {
                    token: 'string',
                    regex: /'/,
                    push: 'twig-qstring',
                },
                {
                    token: 'string',
                    regex: /"/,
                    push: 'twig-qqstring',
                },
            ];

            // {# … #}
            this.$rules['twig-comment'] = [
                {
                    token: 'comment.block.twig-comment-end',
                    regex: /.*#}/,
                    next: 'pop',
                },
                {
                    defaultToken: 'comment.block',
                },
            ];

            // common structures in {{ … }} and {% … %}
            for (const rule of ['twig-output', 'twig-tag']) {
                this.$rules[rule].push(
                    {
                        // <function>(
                        token: ['support.function', '', 'paren.lparen'],
                        regex: `(${environment.functions.join('|')})(\\s*)(\\()`,
                    },
                    {
                        // |<filter>
                        token: (operator, whitespace, filter) => {
                            const isDangerous = ['raw', 'insert_tag_raw'].includes(filter);
                            return [
                                'keyword.operator.other',
                                '',
                                isDangerous ? 'support.function.dangerous' : 'support.function',
                            ];
                        },
                        regex: `(\\|)(\\s*)(${environment.filters.join('|')})`,
                    },
                    {
                        // is <test>
                        token: ['keyword.operator.other', '', 'support.function'],
                        regex: `(is)(\\s+)(${environment.tests.join('|')})`,
                    },
                    {
                        token: 'string',
                        regex: "'",
                        push: 'twig-qstring',
                    },
                    {
                        token: 'string',
                        regex: '"',
                        push: 'twig-qqstring',
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
                        // hex number
                        token: 'constant.numeric',
                        regex: '0[xX][0-9a-fA-F]+\\b',
                    },
                    {
                        // float number
                        token: 'constant.numeric',
                        regex: '[+-]?\\d+(?:(?:\\.\\d*)?(?:[eE][+-]?\\d+)?)?\\b',
                    },
                    {
                        token: 'constant.language.boolean',
                        regex: '(?:true|false)\\b',
                    },
                );
            }

            // Anything else is a variable
            this.$rules['twig-output'].push([
                {
                    token: 'variable',
                    regex: /\w+/,
                },
            ]);

            // "…"
            this.$rules['twig-qqstring'] = [
                {
                    token: 'constant.language.escape',
                    regex: /\\[\\"$#ntr]|#{[^"}]*}/,
                },
                {
                    token: 'string',
                    regex: /"/,
                    next: 'pop',
                },
                {
                    defaultToken: 'string',
                },
            ];

            // '…'
            this.$rules['twig-qstring'] = [
                {
                    token: 'constant.language.escape',
                    regex: /\\[\\'ntr]}/,
                },
                {
                    token: 'string',
                    regex: /'/,
                    next: 'pop',
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
            const tokens = this.editor
                .getSession()
                .getTokens(row)
                .filter((token) => !(token.type === 'text' && /^\s*$/.test(token.value)));

            for (let i = 0; i < tokens.length; i++) {
                if (
                    tokens[i]?.type.split('.').includes('twig-tag-name') &&
                    ['extends', 'use'].includes(tokens[i].value) &&
                    tokens[i + 1]?.type.split('.').includes('string')
                ) {
                    const name = tokens[i + 1].value.replace(/["']/g, '');

                    if (/^@Contao(_.+)?\//.test(name)) {
                        references.push({ name, row, column: tokens[i].start });
                    }

                    i += 1;
                }
            }
        }

        return references;
    }

    analyzeBlocks() {
        const blocks = [];

        for (let row = 0; row < this.editor.getSession().getLength(); row++) {
            const tokens = this.editor
                .getSession()
                .getTokens(row)
                .filter((token) => !(token.type === 'text' && /^\s*$/.test(token.value)));

            for (let i = 0; i < tokens.length; i++) {
                if (
                    tokens[i]?.type.split('.').includes('twig-tag-name') &&
                    tokens[i].value === 'block' &&
                    tokens[i + 1]?.type.split('.').includes('text')
                ) {
                    blocks.push({ name: tokens[i + 1].value.trim(), row, column: tokens[i].start });

                    i += 1;
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
