<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2017 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Tests\FragmentRegistry\ContentElement;

use Contao\ContentModel;
use Contao\CoreBundle\FragmentRegistry\ContentElement\ContentElementRendererInterface;
use Contao\CoreBundle\FragmentRegistry\ContentElement\DelegatingContentElementRenderer;
use Contao\CoreBundle\Tests\TestCase;

class DelegatingContentElementRendererTest extends TestCase
{
    public function testCanBeInstantiated(): void
    {
        $renderer = new DelegatingContentElementRenderer([]);

        $this->assertInstanceOf('Contao\CoreBundle\FragmentRegistry\ContentElement\DelegatingContentElementRenderer', $renderer);
    }

    public function testReturnsTrueIfOneOfTheRenderersSupportsTheModel(): void
    {
        $renderer1 = $this->createMock(ContentElementRendererInterface::class);

        $renderer1
            ->expects($this->once())
            ->method('supports')
            ->willReturn(true)
        ;

        $renderer2 = $this->createMock(ContentElementRendererInterface::class);

        $renderer2
            ->expects($this->never())
            ->method('supports')
        ;

        $renderer = new DelegatingContentElementRenderer([$renderer1, $renderer2]);

        $this->assertTrue($renderer->supports(new ContentModel()));
    }

    public function testReturnsFalseIfNoneOfTheRenderersSupportsTheModel(): void
    {
        $renderer1 = $this->createMock(ContentElementRendererInterface::class);

        $renderer1
            ->expects($this->once())
            ->method('supports')
            ->willReturn(false)
        ;

        $renderer2 = $this->createMock(ContentElementRendererInterface::class);

        $renderer2
            ->expects($this->once())
            ->method('supports')
            ->willReturn(false)
        ;

        $renderer = new DelegatingContentElementRenderer([$renderer1, $renderer2]);

        $this->assertFalse($renderer->supports(new ContentModel()));
    }

    public function testRendersTheFragmentIfOneOfTheRenderersSupportsTheModel(): void
    {
        $renderer1 = $this->createMock(ContentElementRendererInterface::class);

        $renderer1
            ->expects($this->once())
            ->method('supports')
            ->willReturn(true)
        ;

        $renderer1
            ->expects($this->once())
            ->method('render')
            ->willReturn('foobar')
        ;

        $renderer2 = $this->createMock(ContentElementRendererInterface::class);

        $renderer2
            ->expects($this->never())
            ->method('supports')
        ;

        $renderer2
            ->expects($this->never())
            ->method('render')
        ;

        $renderer = new DelegatingContentElementRenderer([$renderer1, $renderer2]);

        $this->assertSame('foobar', $renderer->render(new ContentModel()));
    }
}
