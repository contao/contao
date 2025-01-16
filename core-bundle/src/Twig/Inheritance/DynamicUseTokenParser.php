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
use Twig\Node\Expression\AbstractExpression;
use Twig\Node\Expression\ConstantExpression;
use Twig\Node\Node;
use Twig\Token;
use Twig\TokenParser\AbstractTokenParser;
use Twig\TokenParser\UseTokenParser;

/**
 * This parser is a drop in replacement for the UseTokenParser that adds support
 * for the Contao template hierarchy.
 *
 * @see UseTokenParser
 *
 * @experimental
 */
final class DynamicUseTokenParser extends AbstractTokenParser
{
    public function __construct(private readonly ContaoFilesystemLoader $filesystemLoader)
    {
    }

    public function parse(Token $token): Node
    {
        $templateExpression = $this->parser->getExpressionParser()->parseExpression();
        $stream = $this->parser->getStream();

        if (!$templateExpression instanceof ConstantExpression) {
            throw new SyntaxError('The template references in a "use" statement must be a string.', $stream->getCurrent()->getLine(), $stream->getSourceContext());
        }

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

        if ($contaoTemplateExpression = $this->getContaoTemplateExpression($stream->getSourceContext()->getPath(), $templateExpression)) {
            $templateExpression = $contaoTemplateExpression;
        }

        $this->parser->addTrait(new Node(['template' => $templateExpression, 'targets' => new Node($targets)]));

        return new Node();
    }

    public function getTag(): string
    {
        return 'use';
    }

    private function getContaoTemplateExpression(string $sourcePath, ConstantExpression $name): AbstractExpression|null
    {
        $parts = ContaoTwigUtil::parseContaoName((string) $name->getAttribute('value'));

        if ('Contao' !== ($parts[0] ?? null)) {
            return null;
        }

        return new RuntimeThemeDependentExpression(
            $this->filesystemLoader->getAllDynamicParentsByThemeSlug($parts[1] ?? '', $sourcePath),
        );
    }
}
