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
use Contao\CoreBundle\Twig\Slots\SlotPropertyNode;
use Twig\Compiler;
use Twig\Environment;

class SlotPropertyNodeTest extends TestCase
{
    public function testCompilesSlotPropertyCode(): void
    {
        $compiler = new Compiler($this->createMock(Environment::class));

        (new SlotPropertyNode())->compile($compiler);

        $expectedSource = <<<'SOURCE'

            private array $slots = [];

            SOURCE;

        $this->assertSame($expectedSource, $compiler->getSource());
    }
}
