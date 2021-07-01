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
 * that adds support for the Contao template hierarchy.
 */
class DynamicExtendsTokenParser extends AbstractTokenParser
{
    /**
     * @var TemplateHierarchyInterface
     */
    private $hierarchy;

    /**
     * Track use of inherited templates to prevent endless loops.
     *
     * @var array<string,array<string>>
     */
    private $inheritanceChains = [];

    public function __construct(TemplateHierarchyInterface $hierarchy)
    {
        $this->hierarchy = $hierarchy;
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

        $shortName = $matches[1];
        $sourcePath = $stream->getSourceContext()->getPath();
        $parentName = $this->hierarchy->getDynamicParent($matches[1], $sourcePath);

        // Detect loops
        if (\in_array($parentName, $this->inheritanceChains[$shortName] ?? [], true)) {
            $chain = implode(' -> ', $this->inheritanceChains[$shortName]);

            throw new \LogicException("Loop detected when extending '$parentName': $chain -> &0");
        }

        $this->inheritanceChains[$shortName][] = $parentName;

        // Adjust parent template according to the template hierarchy
        $parent->setAttribute('value', $parentName);
    }
}
