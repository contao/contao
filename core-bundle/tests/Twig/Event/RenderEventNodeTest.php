<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Twig\Event;

use Contao\CoreBundle\Tests\TestCase;
use Contao\CoreBundle\Twig\Event\RenderEventNode;
use Twig\Compiler;
use Twig\Environment;

class RenderEventNodeTest extends TestCase
{
    public function testCompilesProxyCode(): void
    {
        $compiler = new Compiler($this->createMock(Environment::class));

        (new RenderEventNode())->compile($compiler);

        $expectedSource = <<<'SOURCE'
            $context = $this->extensions["Contao\\CoreBundle\\Twig\\Extension\\ContaoExtension"]->dispatchRenderEvent($this->getTemplateName(), $context);


            SOURCE;

        $this->assertSame($expectedSource, $compiler->getSource());
    }
}
