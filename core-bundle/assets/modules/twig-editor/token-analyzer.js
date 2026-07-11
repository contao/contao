export function analyzeReferences(session) {
    const references = [];

    for (let row = 0; row < session.getLength(); row++) {
        const tokens = getNormalizedTokens(session, row);

        for (let i = 0; i < tokens.length; i++) {
            if (
                tokens[i]?.type.split('.').includes('twig-tag-name') &&
                ['extends', 'use'].includes(tokens[i].value) &&
                tokens[i + 1]?.type.split('.').includes('string')
            ) {
                const name = tokens[i + 1].value.replace(/["']/g, '');

                if (/^@Contao(_.+)?\//.test(name)) {
                    references.push({ name, row });
                }

                i += 1;
            }
        }
    }

    return references;
}

export function analyzeBlocks(session) {
    const blocks = [];

    for (let row = 0; row < session.getLength(); row++) {
        const tokens = getNormalizedTokens(session, row);

        for (let i = 0; i < tokens.length; i++) {
            if (
                tokens[i]?.type.split('.').includes('twig-tag-name') &&
                tokens[i].value === 'block' &&
                tokens[i + 1]?.type.split('.').includes('text')
            ) {
                blocks.push({ name: tokens[i + 1].value.trim(), row });

                i += 1;
            }
        }
    }

    return blocks;
}

/**
 * Returns an array of tokens, with empty "text" tokens removed and adjacent
 * "text" tokens combined.
 */
function getNormalizedTokens(session, row) {
    const allTokens = session.getTokens(row);
    const tokens = [];
    let currentTextTokens = [];

    for (const token of allTokens) {
        if (token.type === 'text') {
            currentTextTokens.push(token);

            continue;
        }

        if (currentTextTokens.length) {
            const value = Array.reduce(currentTextTokens, (text, token) => text + token.value, '').trim();

            if (value) {
                tokens.push({ ...currentTextTokens[0], value });
            }

            currentTextTokens = [];
        }

        tokens.push(token);
    }

    return tokens;
}
