<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Twig\Inheritance;

use Twig\Error\SyntaxError;
use Twig\Node\Expression\ConstantExpression;
use Twig\Node\Node;
use Twig\Token;
use Twig\TokenParser\AbstractTokenParser;
use Twig\TokenStream;

/**
 * This parser is a drop in replacement for @\Twig\TokenParser\ExtendsTokenParser.
 * It loosely follows the same implementation but additionally dynamically
 * rewrites 'extends' expressions to follow the Contao template hierarchy.
 */
class DynamicExtendsTokenParser extends AbstractTokenParser
{
    /**
     * @var TemplateHierarchy
     */
    private $templateHierarchy;

    public function __construct(TemplateHierarchy $templateHierarchy)
    {
        $this->templateHierarchy = $templateHierarchy;
    }

    public function parse(Token $token): Node
    {
        $stream = $this->parser->getStream();

        if ($this->parser->peekBlockStack()) {
            throw new SyntaxError('Cannot use "extend" in a block.', $token->getLine(), $stream->getSourceContext());
        }

        if (!$this->parser->isMainScope()) {
            throw new SyntaxError('Cannot use "extend" in a macro.', $token->getLine(), $stream->getSourceContext());
        }

        if (null !== $this->parser->getParent()) {
            throw new SyntaxError('Multiple extends tags are forbidden.', $token->getLine(), $stream->getSourceContext());
        }

        $parent = $this->parser->getExpressionParser()->parseExpression();

        $this->handleDynamicExtends($parent, $stream);

        $this->parser->setParent($parent);

        $stream->expect(Token::BLOCK_END_TYPE);

        return new Node();
    }

    public function getTag(): string
    {
        return 'extends';
    }

    private function handleDynamicExtends(Node $parent, TokenStream $stream): void
    {
        if (!$parent instanceof ConstantExpression) {
            return;
        }

        if (1 !== preg_match('%^@Contao/(.*)%', $parent->getAttribute('value'), $matches)) {
            return;
        }

        $sourcePath = $stream->getSourceContext()->getPath();

        // Adjust parent template according to the template hierarchy
        $parent->setAttribute(
            'value',
            $this->templateHierarchy->getDynamicParent($matches[1], $sourcePath)
        );
    }
}
