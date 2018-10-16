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
use Contao\CoreBundle\Framework\ContaoFrameworkInterface;
use Contao\News;
use Contao\NewsBundle\EventListener\PreviewUrlConvertListener;
use Contao\NewsModel;
use Contao\TestCase\ContaoTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class PreviewUrlConverterListenerTest extends ContaoTestCase
{
    public function testConvertsThePreviewUrl(): void
    {
        $request = new Request();
        $request->query->set('news', 1);
        $request->server->set('SERVER_NAME', 'localhost');
        $request->server->set('SERVER_PORT', 80);

        $requestStack = new RequestStack();
        $requestStack->push($request);

        $newsModel = $this->createMock(NewsModel::class);

        $adapters = [
            NewsModel::class => $this->mockConfiguredAdapter(['findByPk' => $newsModel]),
            News::class => $this->mockConfiguredAdapter(['generateNewsUrl' => 'news/james-wilson-returns.html']),
        ];

        $framework = $this->mockContaoFramework($adapters);
        $event = new PreviewUrlConvertEvent();

        $listener = new PreviewUrlConvertListener($requestStack, $framework);
        $listener->onPreviewUrlConvert($event);

        $this->assertSame('http://localhost/news/james-wilson-returns.html', $event->getUrl());
    }

    public function testDoesNotConvertThePreviewUrlIfTheFrameworkIsNotInitialized(): void
    {
        $framework = $this->createMock(ContaoFrameworkInterface::class);
        $framework
            ->method('isInitialized')
            ->willReturn(false)
        ;

        $event = new PreviewUrlConvertEvent();

        $listener = new PreviewUrlConvertListener(new RequestStack(), $framework);
        $listener->onPreviewUrlConvert($event);

        $this->assertNull($event->getUrl());
    }

    public function testDoesNotConvertThePreviewUrlIfTheNewsParameterIsNotSet(): void
    {
        $request = new Request();
        $request->server->set('SERVER_NAME', 'localhost');
        $request->server->set('SERVER_PORT', 80);

        $requestStack = new RequestStack();
        $requestStack->push($request);

        $framework = $this->mockContaoFramework();
        $event = new PreviewUrlConvertEvent();

        $listener = new PreviewUrlConvertListener($requestStack, $framework);
        $listener->onPreviewUrlConvert($event);

        $this->assertNull($event->getUrl());
    }

    public function testDoesNotConvertThePreviewUrlIfThereIsNoNewsItem(): void
    {
        $request = new Request();
        $request->query->set('news', null);
        $request->server->set('SERVER_NAME', 'localhost');
        $request->server->set('SERVER_PORT', 80);

        $requestStack = new RequestStack();
        $requestStack->push($request);

        $adapters = [
            NewsModel::class => $this->mockConfiguredAdapter(['findByPk' => null]),
        ];

        $framework = $this->mockContaoFramework($adapters);
        $event = new PreviewUrlConvertEvent();

        $listener = new PreviewUrlConvertListener($requestStack, $framework);
        $listener->onPreviewUrlConvert($event);

        $this->assertNull($event->getUrl());
    }
}
