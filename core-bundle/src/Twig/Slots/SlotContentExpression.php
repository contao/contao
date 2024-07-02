<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Twig\Slots;

use Twig\Compiler;
use Twig\Node\Expression\ConstantExpression;
use Twig\Node\NodeOutputInterface;

/**
 * @experimental
 *
 * To mark this expression as safe in all contexts, we extend from
 * ConstantExpression. However, the Twig optimizer currently breaks
 * implementations of child classes (see
 * https://github.com/twigphp/Twig/issues/4119). As a workaround we therefore set
 * the value attribute to null, so that the node is ignored.
 */
final class SlotContentExpression extends ConstantExpression implements NodeOutputInterface
{
    public function __construct(string $name, int $lineno)
    {
        parent::__construct(null, $lineno);

        $this->attributes['name'] = $name;
    }

    public function compile(Compiler $compiler): void
    {
        /*
         * $context['_slots'][<name>]
         */
        $compiler
            ->raw('$context[\'_slots\'][\'')
            ->raw($this->getAttribute('name'))
            ->raw("']")
        ;
    }
}
