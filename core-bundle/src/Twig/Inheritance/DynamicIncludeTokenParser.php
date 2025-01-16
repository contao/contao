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
use Twig\Node\Expression\ArrayExpression;
use Twig\Node\Expression\ConstantExpression;
use Twig\Node\IncludeNode;
use Twig\Node\Node;
use Twig\Token;
use Twig\TokenParser\AbstractTokenParser;
use Twig\TokenParser\IncludeTokenParser;

/**
 * This parser is a drop in replacement for the IncludeTokenParser that adds
 * support for the Contao template hierarchy.
 *
 * @see IncludeTokenParser
 *
 * @experimental
 */
final class DynamicIncludeTokenParser extends AbstractTokenParser
{
    public function __construct(private readonly ContaoFilesystemLoader $filesystemLoader)
    {
    }

    public function parse(Token $token): IncludeNode
    {
        $nameExpression = $this->parser->getExpressionParser()->parseExpression();
        [$variables, $only, $ignoreMissing] = $this->parseArguments();

        // Handle Contao includes
        if ($contaoNameExpression = $this->traverseAndAdjustTemplateNames($nameExpression)) {
            $nameExpression = $contaoNameExpression;
        }

        /** @phpstan-ignore arguments.count */
        return new IncludeNode($nameExpression, $variables, $only, $ignoreMissing, $token->getLine(), $this->getTag());
    }

    public function getTag(): string
    {
        return 'include';
    }

    private function parseArguments(): array
    {
        $stream = $this->parser->getStream();

        $ignoreMissing = false;

        if ($stream->nextIf(Token::NAME_TYPE, 'ignore')) {
            $stream->expect(Token::NAME_TYPE, 'missing');

            $ignoreMissing = true;
        }

        $variables = null;

        if ($stream->nextIf(Token::NAME_TYPE, 'with')) {
            $variables = $this->parser->getExpressionParser()->parseExpression();
        }

        $only = false;

        if ($stream->nextIf(Token::NAME_TYPE, 'only')) {
            $only = true;
        }

        $stream->expect(Token::BLOCK_END_TYPE);

        return [$variables, $only, $ignoreMissing];
    }

    /**
     * Returns a Node if the given $node should be replaced, null otherwise.
     */
    private function traverseAndAdjustTemplateNames(Node $node): Node|null
    {
        if (!$node instanceof ConstantExpression) {
            foreach ($node as $name => $child) {
                try {
                    if ($adjustedNode = $this->traverseAndAdjustTemplateNames($child)) {
                        $node->setNode((string) $name, $adjustedNode);
                    }

                    $this->traverseAndAdjustTemplateNames($child);
                } catch (\LogicException $e) {
                    // Allow missing templates if they are listed in an array like "{% include
                    // ['@Contao/missing', '@Contao/existing'] %}"
                    if (!$node instanceof ArrayExpression) {
                        throw $e;
                    }
                }
            }

            return null;
        }

        $name = (string) $node->getAttribute('value');
        $parts = ContaoTwigUtil::parseContaoName($name);

        if ('Contao' !== ($parts[0] ?? null)) {
            return null;
        }

        try {
            $allFirstByThemeSlug = $this->filesystemLoader->getAllFirstByThemeSlug($parts[1] ?? '');
        } catch (\LogicException $e) {
            throw new \LogicException($e->getMessage().' Did you try to include a non-existent template or a template from a theme directory?', 0, $e);
        }

        return new RuntimeThemeDependentExpression($allFirstByThemeSlug);
    }
}
