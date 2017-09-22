<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2017 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\NewsBundle\Tests\EventListener;

use Contao\CoreBundle\Event\PreviewUrlConvertEvent;
use Contao\CoreBundle\Framework\Adapter;
use Contao\CoreBundle\Framework\ContaoFrameworkInterface;
use Contao\News;
use Contao\NewsBundle\EventListener\PreviewUrlConvertListener;
use Contao\NewsModel;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class PreviewUrlConverterListenerTest extends TestCase
{
    public function testCanBeInstantiated(): void
    {
        $listener = new PreviewUrlConvertListener(new RequestStack(), $this->mockContaoFramework());

        $this->assertInstanceOf('Contao\NewsBundle\EventListener\PreviewUrlConvertListener', $listener);
    }

    public function testConvertsThePreviewUrl(): void
    {
        $request = Request::createFromGlobals();
        $request->query->set('news', 1);
        $request->server->set('SERVER_NAME', 'localhost');
        $request->server->set('SERVER_PORT', 80);

        $requestStack = new RequestStack();
        $requestStack->push($request);

        $event = new PreviewUrlConvertEvent();

        $listener = new PreviewUrlConvertListener($requestStack, $this->mockContaoFramework());
        $listener->onPreviewUrlConvert($event);

        $this->assertSame('http://localhost/news/james-wilson-returns.html', $event->getUrl());
    }

    public function testDoesNotConvertThePreviewUrlIfTheFrameworkIsNotInitialized(): void
    {
        $event = new PreviewUrlConvertEvent();

        $listener = new PreviewUrlConvertListener(new RequestStack(), $this->mockContaoFramework(false));
        $listener->onPreviewUrlConvert($event);

        $this->assertNull($event->getUrl());
    }

    public function testDoesNotConvertThePreviewUrlIfTheNewsParameterIsNotSet(): void
    {
        $request = Request::createFromGlobals();
        $request->server->set('SERVER_NAME', 'localhost');
        $request->server->set('SERVER_PORT', 80);

        $requestStack = new RequestStack();
        $requestStack->push($request);

        $event = new PreviewUrlConvertEvent();

        $listener = new PreviewUrlConvertListener($requestStack, $this->mockContaoFramework());
        $listener->onPreviewUrlConvert($event);

        $this->assertNull($event->getUrl());
    }

    public function testDoesNotConvertThePreviewUrlIfThereIsNoNewsItem(): void
    {
        $request = Request::createFromGlobals();
        $request->query->set('news', null);
        $request->server->set('SERVER_NAME', 'localhost');
        $request->server->set('SERVER_PORT', 80);

        $requestStack = new RequestStack();
        $requestStack->push($request);

        $event = new PreviewUrlConvertEvent();

        $listener = new PreviewUrlConvertListener($requestStack, $this->mockContaoFramework());
        $listener->onPreviewUrlConvert($event);

        $this->assertNull($event->getUrl());
    }

    /**
     * Mocks the Contao framework.
     *
     * @param bool $isInitialized
     *
     * @return ContaoFrameworkInterface
     */
    private function mockContaoFramework(bool $isInitialized = true): ContaoFrameworkInterface
    {
        $framework = $this->createMock(ContaoFrameworkInterface::class);

        $framework
            ->method('isInitialized')
            ->willReturn($isInitialized)
        ;

        $newsAdapter = $this->createMock(Adapter::class);

        $newsAdapter
            ->method('__call')
            ->willReturn('news/james-wilson-returns.html')
        ;

        $newsModelAdapter = $this->createMock(Adapter::class);

        $newsModelAdapter
            ->method('__call')
            ->willReturnCallback(
                function (string $method, array $params): ?NewsModel {
                    $this->assertInternalType('string', $method);

                    if (!empty($params[0])) {
                        return $this->createMock(NewsModel::class);
                    }

                    return null;
                }
            )
        ;

        $framework
            ->method('getAdapter')
            ->willReturnCallback(
                function (string $key) use ($newsAdapter, $newsModelAdapter): ?Adapter {
                    switch ($key) {
                        case News::class:
                            return $newsAdapter;

                        case NewsModel::class:
                            return $newsModelAdapter;
                    }

                    return null;
                }
            )
        ;

        return $framework;
    }
}
