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
 * This parser is a drop in replacement for @\Twig\TokenParser\EmbedTokenParser
 * that adds support for the Contao template hierarchy.
 */
class DynamicEmbedTokenParser extends AbstractTokenParser
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

    public function parse(Token $token)
    {
        // TODO: Implement parse() method.
    }

    public function getTag()
    {
        // TODO: Implement getTag() method.
    }
}
