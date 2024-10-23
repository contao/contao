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
        $this->traverseAndAdjustTemplateNames($sourcePath, $expr);

        $this->parser->setParent($expr);

        $stream->expect(Token::BLOCK_END_TYPE);

        return new Node();
    }

    public function getTag(): string
    {
        return 'extends';
    }

    private function traverseAndAdjustTemplateNames(string $sourcePath, Node $node): void
    {
        if (!$node instanceof ConstantExpression) {
            foreach ($node as $child) {
                try {
                    $this->traverseAndAdjustTemplateNames($sourcePath, $child);
                } catch (\LogicException $e) {
                    // Allow missing templates if they are listed in an array like "{% extends
                    // ['@Contao/missing', '@Contao/existing'] %}"
                    if (!$node instanceof ArrayExpression) {
                        throw $e;
                    }
                }
            }

            return;
        }

        $parts = ContaoTwigUtil::parseContaoName((string) $node->getAttribute('value'));

        if ('Contao' !== ($parts[0] ?? null)) {
            return;
        }

        $parentName = $this->filesystemLoader->getDynamicParent($parts[1] ?? '', $sourcePath);

        // Adjust parent template according to the template hierarchy
        $node->setAttribute('value', $parentName);
    }
}
