<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2017 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Tests\Fragment\PageType;

use Contao\CoreBundle\Fragment\PageType\DelegatingPageTypeRenderer;
use Contao\CoreBundle\Fragment\PageType\PageTypeRendererInterface;
use Contao\PageModel;
use PHPUnit\Framework\TestCase;

class DelegatingPageTypeRendererTest extends TestCase
{
    public function testCanBeInstantiated(): void
    {
        $renderer = new DelegatingPageTypeRenderer();

        $this->assertInstanceOf('Contao\CoreBundle\Fragment\PageType\DelegatingPageTypeRenderer', $renderer);
    }

    public function testReturnsTrueIfOneOfTheRenderersSupportsTheModel(): void
    {
        $renderer1 = $this->createMock(PageTypeRendererInterface::class);

        $renderer1
            ->expects($this->once())
            ->method('supports')
            ->willReturn(true)
        ;

        $renderer2 = $this->createMock(PageTypeRendererInterface::class);

        $renderer2
            ->expects($this->never())
            ->method('supports')
        ;

        $renderer = new DelegatingPageTypeRenderer();
        $renderer->addRenderer($renderer1);
        $renderer->addRenderer($renderer2);

        $this->assertTrue($renderer->supports(new PageModel()));
    }

    public function testReturnsFalseIfNoneOfTheRenderersSupportsTheModel(): void
    {
        $renderer1 = $this->createMock(PageTypeRendererInterface::class);

        $renderer1
            ->expects($this->once())
            ->method('supports')
            ->willReturn(false)
        ;

        $renderer2 = $this->createMock(PageTypeRendererInterface::class);

        $renderer2
            ->expects($this->once())
            ->method('supports')
            ->willReturn(false)
        ;

        $renderer = new DelegatingPageTypeRenderer();
        $renderer->addRenderer($renderer1);
        $renderer->addRenderer($renderer2);

        $this->assertFalse($renderer->supports(new PageModel()));
    }

    public function testRendersTheFragmentIfOneOfTheRenderersSupportsTheModel(): void
    {
        $renderer1 = $this->createMock(PageTypeRendererInterface::class);

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

        $renderer2 = $this->createMock(PageTypeRendererInterface::class);

        $renderer2
            ->expects($this->never())
            ->method('supports')
        ;

        $renderer2
            ->expects($this->never())
            ->method('render')
        ;

        $renderer = new DelegatingPageTypeRenderer();
        $renderer->addRenderer($renderer1);
        $renderer->addRenderer($renderer2);

        $this->assertSame('foobar', $renderer->render(new PageModel()));
    }

    public function testReturnsNullIfNoneOfTheRenderersSupportsTheModel(): void
    {
        $renderer1 = $this->createMock(PageTypeRendererInterface::class);

        $renderer1
            ->expects($this->once())
            ->method('supports')
            ->willReturn(false)
        ;

        $renderer2 = $this->createMock(PageTypeRendererInterface::class);

        $renderer2
            ->expects($this->once())
            ->method('supports')
            ->willReturn(false)
        ;

        $renderer = new DelegatingPageTypeRenderer();
        $renderer->addRenderer($renderer1);
        $renderer->addRenderer($renderer2);

        $this->assertNull($renderer->render(new PageModel()));
    }
}
