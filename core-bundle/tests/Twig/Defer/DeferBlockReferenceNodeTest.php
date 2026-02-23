<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Twig\Defer;

use Contao\CoreBundle\Tests\TestCase;
use Contao\CoreBundle\Twig\Defer\DeferredBlockReferenceNode;
use Twig\Compiler;
use Twig\Environment;

class DeferBlockReferenceNodeTest extends TestCase
{
    public function testCompilesCode(): void
    {
        $compiler = new Compiler($this->createStub(Environment::class));

        $node = new DeferredBlockReferenceNode('__deferred_foo', 0);
        $node->compile($compiler);

        $expectedSource = <<<'SOURCE'
            yield new \Contao\CoreBundle\Twig\Defer\DeferredStringable(fn () => implode(iterator_to_array($this->unwrap()->yieldBlock('__deferred_foo', $context, $blocks), false)));

            SOURCE;

        $this->assertSame($expectedSource, $compiler->getSource());
    }
}
