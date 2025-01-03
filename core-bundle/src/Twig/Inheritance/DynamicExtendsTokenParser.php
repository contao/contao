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
use Contao\CoreBundle\Twig\Loader\ContaoFilesystemLoader;
use Twig\Error\SyntaxError;
use Twig\Node\Expression\ArrayExpression;
use Twig\Node\Expression\ConstantExpression;
use Twig\Node\Node;
use Twig\Token;
use Twig\TokenParser\AbstractTokenParser;
use Twig\TokenParser\ExtendsTokenParser;

/**
 * This parser is a drop in replacement for the ExtendsTokenParser that adds
 * support for the Contao template hierarchy.
 *
 * @see ExtendsTokenParser
 *
 * @experimental
 */
final class DynamicExtendsTokenParser extends AbstractTokenParser
{
    public function __construct(private readonly ContaoFilesystemLoader $filesystemLoader)
    {
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

        $expr = $this->parser->getExpressionParser()->parseExpression();
        $sourcePath = $stream->getSourceContext()->getPath();

        // Handle Contao extends
        $this->parser->setParent($this->traverseAndAdjustTemplateNames($sourcePath, $expr) ?? $expr);

        $stream->expect(Token::BLOCK_END_TYPE);

        return new Node();
    }

    public function getTag(): string
    {
        return 'extends';
    }

    /**
     * Returns a Node if the given $node should be replaced, null otherwise.
     */
    private function traverseAndAdjustTemplateNames(string $sourcePath, Node $node): Node|null
    {
        if (!$node instanceof ConstantExpression) {
            foreach ($node as $name => $child) {
                try {
                    if ($adjustedNode = $this->traverseAndAdjustTemplateNames($sourcePath, $child)) {
                        $node->setNode((string) $name, $adjustedNode);
                    }
                } catch (\LogicException $e) {
                    // Allow missing templates if they are listed in an array like "{% extends
                    // ['@Contao/missing', '@Contao/existing'] %}"
                    if (!$node instanceof ArrayExpression) {
                        throw $e;
                    }
                }
            }

            return null;
        }

        $parts = ContaoTwigUtil::parseContaoName((string) $node->getAttribute('value'));

        if ('Contao' !== ($parts[0] ?? null)) {
            return null;
        }

        return new RuntimeThemeDependentExpression(
            $this->filesystemLoader->getAllDynamicParentsByThemeSlug($parts[1] ?? '', $sourcePath),
        );
    }
}
