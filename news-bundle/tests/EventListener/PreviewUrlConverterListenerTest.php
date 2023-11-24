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
use Contao\News;
use Contao\NewsBundle\EventListener\PreviewUrlConvertListener;
use Contao\NewsModel;
use Contao\TestCase\ContaoTestCase;
use Symfony\Component\HttpFoundation\Request;

class PreviewUrlConverterListenerTest extends ContaoTestCase
{
    public function testConvertsThePreviewUrl(): void
    {
        $request = new Request();
        $request->query->set('news', 1);
        $request->server->set('SERVER_NAME', 'localhost');
        $request->server->set('SERVER_PORT', 80);

        $newsModel = $this->createMock(NewsModel::class);

        $adapters = [
            NewsModel::class => $this->mockConfiguredAdapter(['findByPk' => $newsModel]),
            News::class => $this->mockConfiguredAdapter(['generateNewsUrl' => 'http://localhost/news/james-wilson-returns.html']),
        ];

        $framework = $this->mockContaoFramework($adapters);
        $event = new PreviewUrlConvertEvent($request);

        $listener = new PreviewUrlConvertListener($framework);
        $listener($event);

        $this->assertSame('http://localhost/news/james-wilson-returns.html', $event->getUrl());
    }

    public function testDoesNotConvertThePreviewUrlIfTheFrameworkIsNotInitialized(): void
    {
        $framework = $this->createMock(ContaoFramework::class);
        $framework
            ->method('isInitialized')
            ->willReturn(false)
        ;

        $event = new PreviewUrlConvertEvent(new Request());

        $listener = new PreviewUrlConvertListener($framework);
        $listener($event);

        $this->assertNull($event->getUrl());
    }

    public function testDoesNotConvertThePreviewUrlIfTheNewsParameterIsNotSet(): void
    {
        $request = new Request();
        $request->server->set('SERVER_NAME', 'localhost');
        $request->server->set('SERVER_PORT', 80);

        $framework = $this->mockContaoFramework();
        $event = new PreviewUrlConvertEvent($request);

        $listener = new PreviewUrlConvertListener($framework);
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
            NewsModel::class => $this->mockConfiguredAdapter(['findByPk' => null]),
        ];

        $framework = $this->mockContaoFramework($adapters);
        $event = new PreviewUrlConvertEvent($request);

        $listener = new PreviewUrlConvertListener($framework);
        $listener($event);

        $this->assertNull($event->getUrl());
    }
}
