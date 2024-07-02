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
use Contao\CoreBundle\Twig\Slots\SlotContentExpression;
use Twig\Compiler;
use Twig\Environment;

class SlotContentExpressionTest extends TestCase
{
    public function testCompilesCode(): void
    {
        $compiler = new Compiler($this->createMock(Environment::class));

        $node = new SlotContentExpression('foo', 0);
        $node->compile($compiler);

        $expectedSource = <<<'SOURCE'
            $context['_slots']['foo']
            SOURCE;

        $this->assertSame($expectedSource, $compiler->getSource());
    }
}
