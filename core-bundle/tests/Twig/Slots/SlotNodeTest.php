<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Twig\Slots;

use Contao\CoreBundle\Tests\TestCase;
use Contao\CoreBundle\Twig\Slots\SlotNode;
use Twig\Compiler;
use Twig\Environment;

class SlotNodeTest extends TestCase
{
    public function testCompilesSlotOutputCode(): void
    {
        $compiler = new Compiler($this->createMock(Environment::class));

        (new SlotNode('foo', 0))->compile($compiler);

        $expectedSource = <<<'SOURCE'
            echo $this->slots["foo"] ?? '';

            SOURCE;

        $this->assertSame($expectedSource, $compiler->getSource());
    }
}
