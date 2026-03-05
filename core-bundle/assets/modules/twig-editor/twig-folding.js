const oop = ace.require('ace/lib/oop');
const BaseFoldMode = ace.require('ace/mode/folding/fold_mode').FoldMode;
const Range = ace.require('ace/range').Range;
const TokenIterator = ace.require('ace/token_iterator').TokenIterator;

const TwigFoldMode = function () {
    BaseFoldMode.call(this);
};

oop.inherits(TwigFoldMode, BaseFoldMode);

(function () {
    this.foldingStartMarker = /\{%[-~\s]*(block)[^%]*%}/;
    this.foldingStopMarker = /\{%[-~\s]*(endblock)[^%]*%}/;

    this.getFoldWidget = function (session, _foldStyle, row) {
        const line = session.getLine(row);
        const isStart = this.foldingStartMarker.test(line);
        const isEnd = this.foldingStopMarker.test(line);

        if (isStart && !isEnd) {
            return 'start';
        }
    };

    this.getFoldWidgetRange = function (session, _foldStyle, row) {
        const matchStart = this.foldingStartMarker.exec(session.getLine(row));

        if (matchStart) {
            return twigBlock(session, row, matchStart.index);
        }
    };

    function twigBlock(session, row, column) {
        const stream = new TokenIterator(session, row, column);

        let token;
        let nesting = 0;
        let endColumn = 0;

        while ((token = stream.stepForward())) {
            if (token.type.split('.').includes('twig-tag-start')) {
                endColumn = stream.getCurrentTokenColumn();

                continue;
            }

            if (!token.type.split('.').includes('twig-tag-name')) {
                continue;
            }

            if (token.value === 'block') {
                nesting += 1;
            } else if (token.value === 'endblock') {
                nesting += -1;
            } else {
                continue;
            }

            if (nesting === 0) {
                break;
            }
        }

        if (nesting === 0) {
            return new Range(row, session.getLine(row).length, stream.getCurrentTokenRow(), endColumn);
        }
    }
}).call(TwigFoldMode.prototype);

export default { Mode: TwigFoldMode };
