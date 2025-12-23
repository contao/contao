<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\NewsBundle\Tests\EventListener;

use Contao\CoreBundle\Event\PreviewUrlConvertEvent;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Routing\ContentUrlGenerator;
use Contao\NewsBundle\EventListener\PreviewUrlConvertListener;
use Contao\NewsModel;
use Contao\TestCase\ContaoTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class PreviewUrlConverterListenerTest extends ContaoTestCase
{
    public function testConvertsThePreviewUrl(): void
    {
        $request = new Request();
        $request->query->set('news', 1);
        $request->server->set('SERVER_NAME', 'localhost');
        $request->server->set('SERVER_PORT', 80);

        $newsModel = $this->createStub(NewsModel::class);

        $adapters = [
            NewsModel::class => $this->createConfiguredAdapterStub(['findById' => $newsModel]),
        ];

        $urlGenerator = $this->createMock(ContentUrlGenerator::class);
        $urlGenerator
            ->expects($this->once())
            ->method('generate')
            ->with($newsModel, [], UrlGeneratorInterface::ABSOLUTE_URL)
            ->willReturn('http://localhost/news/james-wilson-returns.html')
        ;

        $framework = $this->createContaoFrameworkStub($adapters);
        $event = new PreviewUrlConvertEvent($request);

        $listener = new PreviewUrlConvertListener($framework, $urlGenerator);
        $listener($event);

        $this->assertSame('http://localhost/news/james-wilson-returns.html', $event->getUrl());
    }

    public function testDoesNotConvertThePreviewUrlIfTheFrameworkIsNotInitialized(): void
    {
        $framework = $this->createStub(ContaoFramework::class);
        $framework
            ->method('isInitialized')
            ->willReturn(false)
        ;

        $urlGenerator = $this->createMock(ContentUrlGenerator::class);
        $urlGenerator
            ->expects($this->never())
            ->method($this->anything())
        ;

        $event = new PreviewUrlConvertEvent(new Request());

        $listener = new PreviewUrlConvertListener($framework, $urlGenerator);
        $listener($event);

        $this->assertNull($event->getUrl());
    }

    public function testDoesNotConvertThePreviewUrlIfTheNewsParameterIsNotSet(): void
    {
        $request = new Request();
        $request->server->set('SERVER_NAME', 'localhost');
        $request->server->set('SERVER_PORT', 80);

        $framework = $this->createContaoFrameworkStub();

        $urlGenerator = $this->createMock(ContentUrlGenerator::class);
        $urlGenerator
            ->expects($this->never())
            ->method($this->anything())
        ;

        $event = new PreviewUrlConvertEvent($request);

        $listener = new PreviewUrlConvertListener($framework, $urlGenerator);
        $listener($event);

        $this->assertNull($event->getUrl());
    }

    public function testDoesNotConvertThePreviewUrlIfThereIsNoNewsItem(): void
    {
        $request = new Request();
        $request->query->set('news', null);
        $request->server->set('SERVER_NAME', 'localhost');
        $request->server->set('SERVER_PORT', 80);

        $adapters = [
            NewsModel::class => $this->createConfiguredAdapterStub(['findById' => null]),
        ];

        $framework = $this->createContaoFrameworkStub($adapters);

        $urlGenerator = $this->createMock(ContentUrlGenerator::class);
        $urlGenerator
            ->expects($this->never())
            ->method($this->anything())
        ;

        $event = new PreviewUrlConvertEvent($request);

        $listener = new PreviewUrlConvertListener($framework, $urlGenerator);
        $listener($event);

        $this->assertNull($event->getUrl());
    }
}
