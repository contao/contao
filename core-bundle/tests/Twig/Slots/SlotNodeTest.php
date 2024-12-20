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
use Twig\Attribute\YieldReady;
use Twig\Compiler;
use Twig\Environment;
use Twig\Node\Expression\ConstantExpression;
use Twig\Node\PrintNode;

class SlotNodeTest extends TestCase
{
    public function testCompilesCode(): void
    {
        $compiler = new Compiler($this->createMock(Environment::class));

        $node = new SlotNode(
            'foo',
            new PrintNode(new ConstantExpression('foo', 0), 0),
            new PrintNode(new ConstantExpression('bar', 0), 0),
            0,
        );

        $node->compile($compiler);

        if (class_exists(YieldReady::class)) {
            $expectedSource = <<<'SOURCE'
                if (isset($context['_slots']['foo'])) {
                    yield "foo";
                } else {
                    yield "bar";
                }

                SOURCE;
        } else {
            $expectedSource = <<<'SOURCE'
                if (isset($context['_slots']['foo'])) {
                    echo "foo";
                } else {
                    echo "bar";
                }

                SOURCE;
        }

        $this->assertSame($expectedSource, $compiler->getSource());
    }
}
