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
use Twig\TokenStream;

/**
 * @internal
 */
final class AddTokenParser extends AbstractTokenParser
{
    public function parse(Token $token): Node
    {
        $stream = $this->parser->getStream();

        // Parse opening tag: {% add to body %} or {% add 'foo' to body after 'bar' %}
        $identifier = null;

        if ($stream->test(Token::STRING_TYPE)) {
            $identifier = $stream->getCurrent()->getValue();
            $stream->next();
        }

        $location = $this->parseLocation($stream);
        [$position, $reference] = $this->parsePosition($stream, $identifier, $token);

        $stream->expect(Token::BLOCK_END_TYPE);

        // Parse closing tag: {% endadd %}
        $body = $this->parser->subparse($this->decideAddEnd(...), true);
        $stream->expect(Token::BLOCK_END_TYPE);

        return new AddNode(
            $body,
            [
                'identifier' => $identifier,
                'location' => $location,
                'position' => $position,
                'reference' => $reference,
            ],
            $token->getLine(),
        );
    }

    /**
     * Keep the name of this function consistent - we use it to guess which token
     * parsers have corresponding end tags.
     *
     * @see \Contao\CoreBundle\Twig\EnvironmentInformation
     */
    public function decideAddEnd(Token $token): bool
    {
        return $token->test('endadd');
    }

    public function getTag(): string
    {
        return 'add';
    }

    private function parseLocation(TokenStream $stream): DocumentLocation
    {
        $stream->expect(Token::NAME_TYPE, 'to');
        $locationToken = $stream->expect(Token::NAME_TYPE, null, '');
        $locationString = $locationToken->getValue();

        if ($location = DocumentLocation::tryFrom($locationString)) {
            return $location;
        }

        $validLocations = array_map(
            static fn (DocumentLocation $location): string => $location->value,
            DocumentLocation::cases(),
        );

        throw new SyntaxError(\sprintf('The parameter "%s" is not a valid location for the "add" tag, use "%s" instead.', $locationString, implode('" or "', $validLocations)));
    }

    /**
     * @return array{string|null, string|null}
     */
    private function parsePosition(TokenStream $stream, string|null $identifier, Token $token): array
    {
        if (!$stream->test(Token::NAME_TYPE, ['before', 'after'])) {
            return [null, null];
        }

        $position = $stream->getCurrent()->getValue();
        $stream->next();

        if (null === $identifier) {
            throw new SyntaxError('An identifier is required when ordering content with the "add" tag.', $token->getLine(), $stream->getSourceContext());
        }

        return [$position, $stream->expect(Token::STRING_TYPE)->getValue()];
    }
}
