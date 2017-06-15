<?php

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

/**
 * Tests the PreviewUrlConverterListener class.
 *
 * @author Leo Feyer <https://github.com/leofeyer>
 */
class PreviewUrlConverterListenerTest extends TestCase
{
    /**
     * Tests the object instantiation.
     */
    public function testInstantiation()
    {
        $listener = new PreviewUrlConvertListener(new RequestStack(), $this->mockContaoFramework());

        $this->assertInstanceOf('Contao\NewsBundle\EventListener\PreviewUrlConvertListener', $listener);
    }

    /**
     * Tests the onPreviewUrlConvert() method.
     */
    public function testOnPreviewUrlConvert()
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

    /**
     * Tests that the listener is bypassed if the framework is not initialized.
     */
    public function testBypassIfFrameworkNotInitialized()
    {
        $event = new PreviewUrlConvertEvent();

        $listener = new PreviewUrlConvertListener(new RequestStack(), $this->mockContaoFramework(false));
        $listener->onPreviewUrlConvert($event);

        $this->assertNull($event->getUrl());
    }

    /**
     * Tests that the listener is bypassed if there is no "news" parameter.
     */
    public function testBypassIfNoNewsParameter()
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

    /**
     * Tests that the listener is bypassed if there is no news item.
     */
    public function testBypassIfNoNewsItem()
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
     * Returns a ContaoFramework instance.
     *
     * @param bool $isInitialized
     *
     * @return ContaoFrameworkInterface
     */
    private function mockContaoFramework($isInitialized = true)
    {
        $framework = $this->createMock(ContaoFrameworkInterface::class);

        $framework
            ->method('isInitialized')
            ->willReturn($isInitialized)
        ;

        $newsAdapter = $this
            ->getMockBuilder(Adapter::class)
            ->disableOriginalConstructor()
            ->setMethods(['generateNewsUrl'])
            ->getMock()
        ;

        $newsAdapter
            ->method('generateNewsUrl')
            ->willReturn('news/james-wilson-returns.html')
        ;

        $newsModelAdapter = $this
            ->getMockBuilder(Adapter::class)
            ->disableOriginalConstructor()
            ->setMethods(['findByPk'])
            ->getMock()
        ;

        $newsModelAdapter
            ->method('findByPk')
            ->willReturnCallback(function ($id) {
                switch ($id) {
                    case null:
                        return null;

                    default:
                        return [];
                }
            })
        ;

        $framework
            ->method('getAdapter')
            ->willReturnCallback(function ($key) use ($newsAdapter, $newsModelAdapter) {
                switch ($key) {
                    case News::class:
                        return $newsAdapter;

                    case NewsModel::class:
                        return $newsModelAdapter;

                    default:
                        return null;
                }
            })
        ;

        return $framework;
    }
}
