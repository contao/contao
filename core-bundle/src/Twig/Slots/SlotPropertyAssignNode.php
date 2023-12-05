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

/**
 * @experimental
 */
final class SlotPropertyAssignNode extends Node
{
    public function compile(Compiler $compiler): void
    {
        $compiler->write('$this->slots = $context[\'_slots\'] ?? [];'."\n");
    }
}
