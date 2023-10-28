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
 */
final class SlotContentExpression extends ConstantExpression implements NodeOutputInterface
{
    public function __construct(string $name, int $lineno)
    {
        parent::__construct($name, $lineno);
    }

    public function compile(Compiler $compiler): void
    {
        /*
         * $context['_slots'][<name>]
         */
        $compiler
            ->raw('$context[\'_slots\'][')
            ->string($this->getAttribute('value'))
            ->raw(']')
        ;
    }
}
