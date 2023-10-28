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
use Twig\Node\Node;
use Twig\Node\NodeOutputInterface;

/**
 * @experimental
 */
final class SlotNode extends Node implements NodeOutputInterface
{
    public function __construct(string $name, Node $body, Node|null $fallback, int $lineno)
    {
        parent::__construct(array_filter(['body' => $body, 'fallback' => $fallback]), ['name' => $name], $lineno);
    }

    public function compile(Compiler $compiler): void
    {
        $name = $this->getAttribute('name');

        /*
         * if(isset($context['_slots'][<name>])) {
         *      <body>
         * }
         * else {
         *      <default body>
         * }
         */
        $compiler
            ->addDebugInfo($this)
            ->write('if(isset($context[\'_slots\'][')
            ->string($name)
            ->raw('])) {'."\n")
            ->indent()
            ->subcompile($this->getNode('body'))
            ->outdent()
        ;

        if ($this->hasNode('fallback')) {
            $compiler
                ->write('} else {'."\n")
                ->indent()
                ->subcompile($this->getNode('fallback'))
                ->outdent()
            ;
        }

        $compiler->write('}'."\n");
    }
}
