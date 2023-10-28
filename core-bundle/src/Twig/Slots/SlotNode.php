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
    public function __construct(string $name, int $lineno)
    {
        parent::__construct([], ['name' => $name], $lineno);
    }

    public function compile(Compiler $compiler): void
    {
        $name = $this->getAttribute('name');

        // echo $this->slots["<name>"] ?? '';
        $compiler
            ->addDebugInfo($this)
            ->write('echo $this->slots[')
            ->string($name)
            ->raw('] ?? \'\';'."\n")
        ;
    }
}
