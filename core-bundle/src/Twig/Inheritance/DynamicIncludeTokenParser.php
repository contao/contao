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
use Twig\Node\IncludeNode;
use Twig\Node\Node;
use Twig\Token;
use Twig\TokenParser\IncludeTokenParser;

/**
 * This parser is a drop in replacement for @\Twig\TokenParser\IncludeTokenParser
 * that adds support for the Contao template hierarchy.
 *
 * @experimental
 */
final class DynamicIncludeTokenParser extends IncludeTokenParser
{
    /**
     * @var TemplateHierarchyInterface
     */
    private $hierarchy;

    public function __construct(TemplateHierarchyInterface $hierarchy)
    {
        $this->hierarchy = $hierarchy;
    }

    public function parse(Token $token): IncludeNode
    {
        $expr = $this->parser->getExpressionParser()->parseExpression();
        [$variables, $only, $ignoreMissing] = $this->parseArguments();

        $this->handleContaoIncludes($expr);

        return new IncludeNode($expr, $variables, $only, $ignoreMissing, $token->getLine(), $this->getTag());
    }

    /**
     * Return the adjusted logical name or the unchanged input if it does not
     * match the Contao Twig namespace.
     */
    public static function adjustTemplateName(string $name, TemplateHierarchyInterface $hierarchy): string
    {
        $parts = ContaoTwigUtil::parseContaoName($name);

        if ('Contao' !== ($parts[0] ?? null)) {
            return $name;
        }

        try {
            return $hierarchy->getFirst($parts[1] ?? '');
        } catch (\LogicException $e) {
            throw new \LogicException($e->getMessage().' Did you try to include a non-existent template or a template from a theme directory?', 0, $e);
        }
    }

    private function handleContaoIncludes(Node $expr): void
    {
        TokenParserHelper::traverseConstantExpressions(
            $expr,
            function (Node $node): void {
                $name = (string) $node->getAttribute('value');
                $adjustedName = self::adjustTemplateName($name, $this->hierarchy);

                if ($name !== $adjustedName) {
                    $node->setAttribute('value', $adjustedName);
                }
            }
        );
    }
}
