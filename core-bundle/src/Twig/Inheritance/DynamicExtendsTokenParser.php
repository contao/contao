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

use Contao\CoreBundle\Twig\ContaoTwigUtil;
use Twig\Error\SyntaxError;
use Twig\Node\Node;
use Twig\Token;
use Twig\TokenParser\AbstractTokenParser;
use Twig\TokenStream;

/**
 * This parser is a drop in replacement for @\Twig\TokenParser\ExtendsTokenParser
 * that adds support for the Contao template hierarchy.
 *
 * @experimental
 */
final class DynamicExtendsTokenParser extends AbstractTokenParser
{
    /**
     * @var TemplateHierarchyInterface
     */
    private $hierarchy;

    public function __construct(TemplateHierarchyInterface $hierarchy)
    {
        $this->hierarchy = $hierarchy;
    }

    public function parse(Token $token): Node
    {
        $stream = $this->parser->getStream();

        if ($this->parser->peekBlockStack()) {
            throw new SyntaxError('Cannot use "extends" in a block.', $token->getLine(), $stream->getSourceContext());
        }

        if (!$this->parser->isMainScope()) {
            throw new SyntaxError('Cannot use "extends" in a macro.', $token->getLine(), $stream->getSourceContext());
        }

        if (null !== $this->parser->getParent()) {
            throw new SyntaxError('Multiple extends tags are forbidden.', $token->getLine(), $stream->getSourceContext());
        }

        $expr = $this->parser->getExpressionParser()->parseExpression();

        $this->handleContaoExtends($expr, $stream);

        $this->parser->setParent($expr);

        $stream->expect(Token::BLOCK_END_TYPE);

        return new Node();
    }

    public function getTag(): string
    {
        return 'extends';
    }

    private function handleContaoExtends(Node $expr, TokenStream $stream): void
    {
        TokenParserHelper::traverseConstantExpressions(
            $expr,
            function (Node $node) use ($stream): void {
                $parts = ContaoTwigUtil::parseContaoName($node->getAttribute('value'));

                if ('Contao' !== ($parts[0] ?? null)) {
                    return;
                }

                $sourcePath = $stream->getSourceContext()->getPath();
                $parentName = $this->hierarchy->getDynamicParent($parts[1] ?? '', $sourcePath);

                // Adjust parent template according to the template hierarchy
                $node->setAttribute('value', $parentName);
            }
        );
    }
}
