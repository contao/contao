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
use Contao\CoreBundle\Twig\Defer\DeferredStringable;
use Contao\CoreBundle\Twig\Renderer\DeferredRenderer;
use Twig\Environment;
use Twig\Template;
use Twig\TemplateWrapper;

class DeferredRendererTest extends TestCase
{
    public function testRendersTemplate(): void
    {
        $template = $this->createStub(Template::class);
        $template
            ->method('yield')
            ->willReturn(new \ArrayIterator([
                new DeferredStringable(static fn () => 'some'),
                ' content',
            ]))
        ;

        $twig = $this->createStub(Environment::class);
        $twig
            ->method('load')
            ->with('foo.html.twig')
            ->willReturn(new TemplateWrapper($twig, $template))
        ;

        $renderer = new DeferredRenderer($twig);

        $this->assertSame('some content', $renderer->render('foo.html.twig', ['foo' => 'bar']));
    }
}
