<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2017 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Tests\Fragment\FrontendModule;

use Contao\CoreBundle\Fragment\FrontendModule\DelegatingFrontendModuleRenderer;
use Contao\CoreBundle\Fragment\FrontendModule\FrontendModuleRendererInterface;
use Contao\ModuleModel;
use PHPUnit\Framework\TestCase;

class DelegatingFrontendModuleRendererTest extends TestCase
{
    public function testCanBeInstantiated(): void
    {
        $renderer = new DelegatingFrontendModuleRenderer();

        $this->assertInstanceOf('Contao\CoreBundle\Fragment\FrontendModule\DelegatingFrontendModuleRenderer', $renderer);
    }

    public function testReturnsTrueIfOneOfTheRenderersSupportsTheModel(): void
    {
        $renderer1 = $this->createMock(FrontendModuleRendererInterface::class);

        $renderer1
            ->expects($this->once())
            ->method('supports')
            ->willReturn(true)
        ;

        $renderer2 = $this->createMock(FrontendModuleRendererInterface::class);

        $renderer2
            ->expects($this->never())
            ->method('supports')
        ;

        $renderer = new DelegatingFrontendModuleRenderer();
        $renderer->addRenderer($renderer1);
        $renderer->addRenderer($renderer2);

        $this->assertTrue($renderer->supports(new ModuleModel()));
    }

    public function testReturnsFalseIfNoneOfTheRenderersSupportsTheModel(): void
    {
        $renderer1 = $this->createMock(FrontendModuleRendererInterface::class);

        $renderer1
            ->expects($this->once())
            ->method('supports')
            ->willReturn(false)
        ;

        $renderer2 = $this->createMock(FrontendModuleRendererInterface::class);

        $renderer2
            ->expects($this->once())
            ->method('supports')
            ->willReturn(false)
        ;

        $renderer = new DelegatingFrontendModuleRenderer();
        $renderer->addRenderer($renderer1);
        $renderer->addRenderer($renderer2);

        $this->assertFalse($renderer->supports(new ModuleModel()));
    }

    public function testRendersTheFragmentIfOneOfTheRenderersSupportsTheModel(): void
    {
        $renderer1 = $this->createMock(FrontendModuleRendererInterface::class);

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

        $renderer2 = $this->createMock(FrontendModuleRendererInterface::class);

        $renderer2
            ->expects($this->never())
            ->method('supports')
        ;

        $renderer2
            ->expects($this->never())
            ->method('render')
        ;

        $renderer = new DelegatingFrontendModuleRenderer();
        $renderer->addRenderer($renderer1);
        $renderer->addRenderer($renderer2);

        $this->assertSame('foobar', $renderer->render(new ModuleModel()));
    }

    public function testReturnsNullIfNoneOfTheRenderersSupportsTheModel(): void
    {
        $renderer1 = $this->createMock(FrontendModuleRendererInterface::class);

        $renderer1
            ->expects($this->once())
            ->method('supports')
            ->willReturn(false)
        ;

        $renderer2 = $this->createMock(FrontendModuleRendererInterface::class);

        $renderer2
            ->expects($this->once())
            ->method('supports')
            ->willReturn(false)
        ;

        $renderer = new DelegatingFrontendModuleRenderer();
        $renderer->addRenderer($renderer1);
        $renderer->addRenderer($renderer2);

        $this->assertNull($renderer->render(new ModuleModel()));
    }
}
