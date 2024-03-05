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
use Twig\TemplateWrapper;
use Twig\Token;
use Twig\TokenParser\AbstractTokenParser;

/**
 * This parser is a drop in replacement for @\Twig\TokenParser\IncludeTokenParser
 * that adds support for the Contao template hierarchy.
 *
 * @experimental
 */
final class DynamicIncludeTokenParser extends AbstractTokenParser
{
    private ContaoFilesystemLoader $filesystemLoader;

    public function __construct(ContaoFilesystemLoader $filesystemLoader)
    {
        $this->filesystemLoader = $filesystemLoader;
    }

    public function parse(Token $token): IncludeNode
    {
        $expr = $this->parser->getExpressionParser()->parseExpression();
        [$variables, $only, $ignoreMissing] = $this->parseArguments();

        // Handle Contao includes
        $this->traverseAndAdjustTemplateNames($expr);

        return new IncludeNode($expr, $variables, $only, $ignoreMissing, $token->getLine(), $this->getTag());
    }

    public function getTag(): string
    {
        return 'include';
    }

    /**
     * Return the adjusted logical name or the unchanged input if it does not
     * match the Contao Twig namespace.
     *
     * @param string|TemplateWrapper $name
     *
     * @return string|TemplateWrapper
     */
    public static function adjustTemplateName($name, ContaoFilesystemLoader $filesystemLoader)
    {
        if (!\is_string($name)) {
            if ($name instanceof TemplateWrapper) {
                return $name;
            }

            throw new \TypeError(sprintf('Argument 1 passed to %s() must be of the type string|TemplateWrapper, %s given', __METHOD__, get_debug_type($name)));
        }

        $parts = ContaoTwigUtil::parseContaoName($name);

        if ('Contao' !== ($parts[0] ?? null)) {
            return $name;
        }

        try {
            return $filesystemLoader->getFirst($parts[1] ?? '');
        } catch (\LogicException $e) {
            throw new \LogicException($e->getMessage().' Did you try to include a non-existent template or a template from a theme directory?', 0, $e);
        }
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

    private function traverseAndAdjustTemplateNames(Node $node): void
    {
        if (!$node instanceof ConstantExpression) {
            foreach ($node as $child) {
                try {
                    $this->traverseAndAdjustTemplateNames($child);
                } catch (\LogicException $e) {
                    // Allow missing templates if they are listed in an array
                    // like "{% include ['@Contao/missing', '@Contao/existing'] %}"
                    if (!$node instanceof ArrayExpression) {
                        throw $e;
                    }
                }
            }

            return;
        }

        $name = (string) $node->getAttribute('value');
        $adjustedName = self::adjustTemplateName($name, $this->filesystemLoader);

        if ($name !== $adjustedName) {
            $node->setAttribute('value', $adjustedName);
        }
    }
}
