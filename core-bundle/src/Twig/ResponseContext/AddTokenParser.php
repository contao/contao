<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Twig\ResponseContext;

use Twig\Error\SyntaxError;
use Twig\Node\Node;
use Twig\Token;
use Twig\TokenParser\AbstractTokenParser;

class AddTokenParser extends AbstractTokenParser
{
    public function __construct(private readonly string $extensionName)
    {
    }

    public function parse(Token $token): Node
    {
        $stream = $this->parser->getStream();

        // Parse opening tag: {% add to body %} or {% add 'foo' to body %}
        $identifier = null;

        if ($stream->test(Token::STRING_TYPE)) {
            $identifier = $stream->getCurrent()->getValue();
            $stream->next();
        }

        $stream->expect(Token::NAME_TYPE, 'to');
        $locationToken = $stream->expect(Token::NAME_TYPE, null, '');
        $locationString = $locationToken->getValue();

        if (!$location = DocumentLocation::tryFrom($locationString)) {
            $validLocations = array_map(
                static fn (DocumentLocation $location): string => $location->value,
                DocumentLocation::cases()
            );

            throw new SyntaxError(sprintf('The parameter "%s" is not a valid location for the "add" tag, use "%s" instead.', $locationString, implode('" or "', $validLocations)));
        }

        $stream->expect(Token::BLOCK_END_TYPE);

        // Parse closing tag: {% endadd %}
        $body = $this->parser->subparse($this->decideAddEnd(...), true);
        $stream->expect(Token::BLOCK_END_TYPE);

        return new AddNode($this->extensionName, $body, $identifier, $location, $token->getLine());
    }

    public function decideAddEnd(Token $token): bool
    {
        return $token->test('endadd');
    }

    public function getTag(): string
    {
        return 'add';
    }
}
