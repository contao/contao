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
use Contao\CoreBundle\Twig\Slots\SlotPropertyAssignNode;
use Twig\Compiler;
use Twig\Environment;

class SlotPropertyAssignNodeTest extends TestCase
{
    public function testCompilesSlotAssignmentCode(): void
    {
        $compiler = new Compiler($this->createMock(Environment::class));

        (new SlotPropertyAssignNode())->compile($compiler);

        $expectedSource = <<<'SOURCE'
            $this->slots = $context['_slots'] ?? [];
            unset($context['_slots']);

            SOURCE;

        $this->assertSame($expectedSource, $compiler->getSource());
    }
}
