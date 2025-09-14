const oop = ace.require('ace/lib/oop');
const TwigMode = ace.require('ace/mode/twig').Mode;
const TextHighlightRules = ace.require('ace/mode/text_highlight_rules').TextHighlightRules;
const HtmlHighlightRules = ace.require('ace/mode/html_highlight_rules').HtmlHighlightRules;
const MatchingBraceOutdent = ace.require('ace/mode/matching_brace_outdent').MatchingBraceOutdent;

function createContaoTwigHighlightRules(type, environment) {
    const ContaoTwigHighlightRules = function () {
        if (type === 'html') {
            HtmlHighlightRules.call(this);
        } else {
            TextHighlightRules.call(this);
        }

        // Add rules to enter Twig block delimiters to all existing rules
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

        // Twig output {{ … }}
        this.$rules['twig-output'] = [
            {
                token: 'meta.tag.twig-output-end',
                regex: /[-~]?}}/,
                next: 'pop',
            },
        ];

        // Twig tag {% … %}
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

        // Twig comment {# … #}
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

        // Common Twig structures
        for (const rule of ['twig-output', 'twig-tag']) {
            this.$rules[rule].push(
                {
                    // <function>(
                    token: ['support.function.twig-function', '', 'paren.lparen'],
                    regex: `(${environment.functions.join('|')})(\\s*)(\\()`,
                },
                {
                    // |<filter>
                    token: (operator, whitespace, filter) => {
                        const isDangerous = ['raw', 'insert_tag_raw'].includes(filter);
                        return [
                            'keyword.operator.other',
                            '',
                            isDangerous ? 'support.function.twig-function.dangerous' : 'support.function.twig-function',
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

        this.$rules['twig-output'].push([
            {
                token: 'variable',
                regex: /\w+/,
            },
        ]);

        // Twig strings "…" / '…'
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

    oop.inherits(ContaoTwigHighlightRules, type === 'html' ? HtmlHighlightRules : TextHighlightRules);

    return ContaoTwigHighlightRules;
}

const ContaoTwigMode = function (type, environment) {
    TwigMode.call(this);
    this.HighlightRules = createContaoTwigHighlightRules(type, environment);
    this.$outdent = new MatchingBraceOutdent();
};

oop.inherits(ContaoTwigMode, TwigMode);

(function () {
    this.$id = 'ace/mode/contao-twig';
}).call(ContaoTwigMode.prototype);

export default { Mode: ContaoTwigMode };
