<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2018 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\NewsBundle\Tests\EventListener;

use Contao\CoreBundle\Event\PreviewUrlCreateEvent;
use Contao\CoreBundle\Framework\Adapter;
use Contao\CoreBundle\Framework\ContaoFrameworkInterface;
use Contao\NewsBundle\EventListener\PreviewUrlCreateListener;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Tests the PreviewUrlCreateListener class.
 *
 * @author Leo Feyer <https://github.com/leofeyer>
 */
class PreviewUrlCreateListenerTest extends TestCase
{
    /**
     * Tests the object instantiation.
     */
    public function testCanBeInstantiated()
    {
        $listener = new PreviewUrlCreateListener(new RequestStack(), $this->mockContaoFramework());

        $this->assertInstanceOf('Contao\NewsBundle\EventListener\PreviewUrlCreateListener', $listener);
    }

    /**
     * Tests the onPreviewUrlCreate() method.
     */
    public function testCreatesThePreviewUrl()
    {
        $request = Request::createFromGlobals();

        $requestStack = new RequestStack();
        $requestStack->push($request);

        $event = new PreviewUrlCreateEvent('news', 1);

        $listener = new PreviewUrlCreateListener($requestStack, $this->mockContaoFramework());
        $listener->onPreviewUrlCreate($event);

        $this->assertSame('news=1', $event->getQuery());
    }

    /**
     * Tests that the listener is bypassed if the framework is not initialized.
     */
    public function testDoesNotCreateThePreviewUrlIfTheFrameworkIsNotInitialized()
    {
        $event = new PreviewUrlCreateEvent('news', 1);

        $listener = new PreviewUrlCreateListener(new RequestStack(), $this->mockContaoFramework(false));
        $listener->onPreviewUrlCreate($event);

        $this->assertNull($event->getQuery());
    }

    /**
     * Tests that the listener is bypassed if the key is not "news".
     */
    public function testDoesNotCreateThePreviewUrlIfTheNewsParameterIsNotSet()
    {
        $event = new PreviewUrlCreateEvent('calendar', 1);

        $listener = new PreviewUrlCreateListener(new RequestStack(), $this->mockContaoFramework());
        $listener->onPreviewUrlCreate($event);

        $this->assertNull($event->getQuery());
    }

    /**
     * Tests that the listener is bypassed on the news archive list page.
     */
    public function testDoesNotCreateThePreviewUrlOnTheArchiveListPage()
    {
        $request = Request::createFromGlobals();
        $request->query->set('table', 'tl_news');

        $requestStack = new RequestStack();
        $requestStack->push($request);

        $event = new PreviewUrlCreateEvent('news', 1);

        $listener = new PreviewUrlCreateListener($requestStack, $this->mockContaoFramework());
        $listener->onPreviewUrlCreate($event);

        $this->assertNull($event->getQuery());
    }

    /**
     * Tests that the ID is overwritten if the news settings are edited.
     */
    public function testOverwritesTheIdIfTheArchiveSettingsAreEdited()
    {
        $request = Request::createFromGlobals();
        $request->query->set('act', 'edit');
        $request->query->set('table', 'tl_news');
        $request->query->set('id', 2);

        $requestStack = new RequestStack();
        $requestStack->push($request);

        $event = new PreviewUrlCreateEvent('news', 1);

        $listener = new PreviewUrlCreateListener($requestStack, $this->mockContaoFramework());
        $listener->onPreviewUrlCreate($event);

        $this->assertSame('news=2', $event->getQuery());
    }

    /**
     * Tests that the listener is bypassed if there is no news item.
     */
    public function testDoesNotCreateThePreviewUrlIfThereIsNoNewsItem()
    {
        $request = Request::createFromGlobals();

        $requestStack = new RequestStack();
        $requestStack->push($request);

        $event = new PreviewUrlCreateEvent('news', null);

        $listener = new PreviewUrlCreateListener($requestStack, $this->mockContaoFramework());
        $listener->onPreviewUrlCreate($event);

        $this->assertNull($event->getQuery());
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
                        return (object) ['id' => $id];
                }
            })
        ;

        $framework
            ->method('getAdapter')
            ->willReturnCallback(function ($key) use ($newsModelAdapter) {
                switch ($key) {
                    case 'Contao\NewsModel':
                        return $newsModelAdapter;

                    default:
                        return null;
                }
            })
        ;

        return $framework;
    }
}
