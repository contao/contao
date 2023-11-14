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
use Twig\Node\Expression\ConstantExpression;
use Twig\Node\Node;
use Twig\Token;
use Twig\TokenParser\AbstractTokenParser;
use Twig\TokenParser\UseTokenParser;

/**
 * This parser is a drop in replacement for the UseTokenParser
 * that adds support for the Contao template hierarchy.
 *
 * @see UseTokenParser
 *
 * @experimental
 */
final class DynamicUseTokenParser extends AbstractTokenParser
{
    public function __construct(private readonly TemplateHierarchyInterface $hierarchy)
    {
    }

    #[\Override]
    public function parse(Token $token): Node
    {
        $template = $this->parser->getExpressionParser()->parseExpression();
        $stream = $this->parser->getStream();

        if (!$template instanceof ConstantExpression) {
            throw new SyntaxError('The template references in a "use" statement must be a string.', $stream->getCurrent()->getLine(), $stream->getSourceContext());
        }

        $this->adjustTemplateName($stream->getSourceContext()->getPath(), $template);

        $targets = [];

        if ($stream->nextIf('with')) {
            while (true) {
                $name = $stream->expect(Token::NAME_TYPE)->getValue();
                $alias = $name;

                if ($stream->nextIf('as')) {
                    $alias = $stream->expect(Token::NAME_TYPE)->getValue();
                }

                $targets[$name] = new ConstantExpression($alias, -1);

                if (!$stream->nextIf(Token::PUNCTUATION_TYPE, ',')) {
                    break;
                }
            }
        }

        $stream->expect(Token::BLOCK_END_TYPE);

        $this->parser->addTrait(new Node(['template' => $template, 'targets' => new Node($targets)]));

        return new Node();
    }

    #[\Override]
    public function getTag(): string
    {
        return 'use';
    }

    private function adjustTemplateName(string $sourcePath, ConstantExpression $node): void
    {
        $parts = ContaoTwigUtil::parseContaoName((string) $node->getAttribute('value'));

        if ('Contao' !== ($parts[0] ?? null)) {
            return;
        }

        $nextOrFirst = $this->hierarchy->getDynamicParent($parts[1] ?? '', $sourcePath);

        // Adjust parent template according to the template hierarchy
        $node->setAttribute('value', $nextOrFirst);
    }
}
