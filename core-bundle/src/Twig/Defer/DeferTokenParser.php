<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Twig\Defer;

use Twig\Node\BlockNode;
use Twig\Node\EmptyNode;
use Twig\Node\Node;
use Twig\Token;
use Twig\TokenParser\AbstractTokenParser;
use Twig\TokenStream;

/**
 * @internal
 */
class DeferTokenParser extends AbstractTokenParser
{
    public const PREFIX = '__deferred_';

    /**
     * @var array<string, int>
     */
    public static array $nextIndexByName = [];

    public function parse(Token $token): Node
    {
        $stream = $this->parser->getStream();
        $name = $this->getUniqueName($stream);

        $this->parser->setBlock($name, $block = new BlockNode($name, new EmptyNode(), $token->getLine()));
        $this->parser->pushLocalScope();
        $this->parser->pushBlockStack($name);

        $stream->expect(Token::BLOCK_END_TYPE);
        $body = $this->parser->subparse($this->decideDeferEnd(...), true);
        $stream->expect(Token::BLOCK_END_TYPE);

        $block->setNode('body', $body);
        $this->parser->popBlockStack();
        $this->parser->popLocalScope();

        return new DeferredBlockReferenceNode($name, $token->getLine());
    }

    public function decideDeferEnd(Token $token): bool
    {
        return $token->test('enddefer');
    }

    public function getTag(): string
    {
        return 'defer';
    }

    private function getUniqueName(TokenStream $stream): string
    {
        $templateName = $stream->getSourceContext()->getName();
        $index = self::$nextIndexByName[$templateName] ?? 0;

        self::$nextIndexByName[$templateName] = $index + 1;

        return self::PREFIX.hash('xxh3', $stream->getSourceContext()->getName()."\0".$index);
    }
}
