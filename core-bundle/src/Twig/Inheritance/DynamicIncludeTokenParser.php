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
use Twig\Node\Expression\AbstractExpression;
use Twig\Node\Expression\ConstantExpression;
use Twig\Node\IncludeNode;
use Twig\Node\Node;
use Twig\Token;
use Twig\TokenParser\AbstractTokenParser;
use Twig\TokenParser\IncludeTokenParser;
use Twig\TokenStream;

/**
 * This parser is a drop in replacement for @\Twig\TokenParser\IncludeTokenParser.
 * that adds support for the Contao template hierarchy.
 */
class DynamicIncludeTokenParser extends IncludeTokenParser
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

    public function parse(Token $token): IncludeNode
    {
        $expr = $this->parser->getExpressionParser()->parseExpression();
        [$variables, $only, $ignoreMissing] = $this->parseArguments();

        $this->handleDynamicIncludes($expr);

        return new IncludeNode($expr, $variables, $only, $ignoreMissing, $token->getLine(), $this->getTag());
    }

    private function handleDynamicIncludes(AbstractExpression $expr): void
    {
        throw new \LogicException('not implemented');

    }
}
