<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Twig\Renderer;

use Contao\CoreBundle\Tests\TestCase;
use Contao\CoreBundle\Twig\Renderer\DefaultRenderer;
use Twig\Environment;

class DefaultRendererTest extends TestCase
{
    public function testRendersTemplate(): void
    {
        $twig = $this->createMock(Environment::class);
        $twig
            ->expects($this->once())
            ->method('render')
            ->with('foo.html.twig', ['foo' => 'bar'])
            ->willReturn('content')
        ;

        $renderer = new DefaultRenderer($twig);

        $this->assertSame('content', $renderer->render('foo.html.twig', ['foo' => 'bar']));
    }
}
