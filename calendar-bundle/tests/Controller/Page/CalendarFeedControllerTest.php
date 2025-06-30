<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CalendarBundle\Tests\Controller\Page;

use Contao\CalendarBundle\Controller\Page\CalendarFeedController;
use Contao\CalendarBundle\Event\FetchEventsForFeedEvent;
use Contao\CalendarBundle\Event\TransformEventForFeedEvent;
use Contao\CoreBundle\Asset\ContaoContext;
use Contao\CoreBundle\Cache\CacheTagManager;
use Contao\CoreBundle\Routing\Page\PageRoute;
use Contao\PageModel;
use Contao\System;
use Contao\TestCase\ContaoTestCase;
use FeedIo\Feed\Item;
use FeedIo\Specification;
use PHPUnit\Framework\Attributes\DataProvider;
use Psr\Log\NullLogger;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpFoundation\Request;

class CalendarFeedControllerTest extends ContaoTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        System::setContainer($this->getContainerWithContaoConfiguration());
    }

    protected function tearDown(): void
    {
        $this->resetStaticProperties([System::class]);

        parent::tearDown();
    }

    public function testReturnsValidUrlSuffixes(): void
    {
        $controller = $this->getController();

        $this->assertSame(['.xml', '.json'], $controller->getUrlSuffixes());
    }

    #[DataProvider('getXMLFeedFormats')]
    #[DataProvider('getJSONFeedFormats')]
    public function testConfiguresPageRoute(string $format, string $suffix): void
    {
        $pageModel = $this->mockClassWithProperties(PageModel::class, [
            'id' => 42,
            'title' => 'Next events',
            'alias' => 'next-events',
            'feedFormat' => $format,
            'urlPrefix' => '',
            'urlSuffix' => '',
        ]);

        $route = new PageRoute($pageModel);

        $controller = $this->getController();
        $controller->configurePageRoute($route);

        $this->assertSame($suffix, $route->getUrlSuffix());
    }

    public function testThrowsExceptionOnInvalidFeedFormat(): void
    {
        $pageModel = $this->mockClassWithProperties(PageModel::class, [
            'id' => 42,
            'title' => 'Next events',
            'alias' => 'next-events',
            'feedFormat' => 'foo',
            'urlPrefix' => '',
            'urlSuffix' => '',
        ]);

        $route = new PageRoute($pageModel);
        $controller = $this->getController();

        $this->expectException(\RuntimeException::class);

        $controller->configurePageRoute($route);
    }

    public function testReturnsEmptyFeed(): void
    {
        $pageModel = $this->mockClassWithProperties(PageModel::class, [
            'id' => 42,
            'title' => 'Next events',
            'alias' => 'next-events',
            'feedDescription' => 'Get the next events',
            'feedFormat' => 'rss',
            'language' => 'en',
            'eventCalendars' => serialize([8472]),
        ]);

        $container = $this->getContainerWithContaoConfiguration();
        $container->set('contao.framework', $this->mockContaoFramework());
        $container->set('event_dispatcher', $this->createMock(EventDispatcher::class));

        $cacheTagManager = $this->createMock(CacheTagManager::class);
        $cacheTagManager
            ->expects($this->once())
            ->method('tagWith')
            ->with(['contao.db.tl_calendar.8472'])
        ;

        $container->set('contao.cache.tag_manager', $cacheTagManager);

        $controller = $this->getController();
        $controller->setContainer($container);

        $request = Request::create('https://example.org/next-events.xml');
        $response = $controller($request, $pageModel);

        $this->assertSame(200, $response->getStatusCode());
    }

    #[DataProvider('getXMLFeedFormats')]
    public function testProperlyEncodesXMLEntities(string $format): void
    {
        $pageModel = $this->mockClassWithProperties(PageModel::class, [
            'id' => 42,
            'title' => 'Next events &lt;/channel&gt;',
            'alias' => 'next-events',
            'feedDescription' => 'Get the next events &lt;/channel&gt;',
            'feedFormat' => $format,
            'language' => 'en',
        ]);

        $container = $this->getContainerWithContaoConfiguration();
        $container->set('contao.framework', $this->mockContaoFramework());
        $container->set('event_dispatcher', $this->createMock(EventDispatcher::class));

        $cacheTagManager = $this->createMock(CacheTagManager::class);
        $cacheTagManager
            ->expects($this->never())
            ->method('tagWith')
        ;

        $container->set('contao.cache.tag_manager', $this->createMock(CacheTagManager::class));

        $controller = $this->getController();
        $controller->setContainer($container);

        $request = Request::create('https://example.org/next-events.xml');
        $response = $controller($request, $pageModel);

        $this->assertSame(200, $response->getStatusCode());

        $document = new \DOMDocument('1.0', 'utf-8');
        $document->loadXML($response->getContent());

        $this->assertSame('Next events </channel>', $document->getElementsByTagName('title')->item(0)->textContent);
        $this->assertSame('Get the next events </channel>', $document->getElementsByTagName('description')->item(0)->textContent);
    }

    #[DataProvider('getXMLFeedFormats')]
    #[DataProvider('getJSONFeedFormats')]
    public function testReturnsFeedInCorrectFormat(string $format, string $suffix, string $url, string $contentType): void
    {
        $pageModel = $this->mockClassWithProperties(PageModel::class, [
            'id' => 42,
            'title' => 'Next events',
            'alias' => 'next-events',
            'feedFormat' => $format,
        ]);

        $container = $this->getContainerWithContaoConfiguration();
        $container->set('contao.framework', $this->mockContaoFramework());
        $container->set('contao.cache.tag_manager', $this->createMock(CacheTagManager::class));

        $dispatcher = $this->createMock(EventDispatcher::class);
        $dispatcher
            ->method('dispatch')
            ->willReturnCallback(
                function ($event) {
                    if ($event instanceof FetchEventsForFeedEvent) {
                        $event->setEvents($this->getEvents());

                        return $event;
                    }

                    if ($event instanceof TransformEventForFeedEvent) {
                        $event->setItem(new Item());

                        return $event;
                    }

                    $this->fail('Unexpected event: '.$event::class);
                },
            )
        ;

        $container->set('event_dispatcher', $dispatcher);

        $controller = $this->getController();
        $controller->setContainer($container);

        $response = $controller(Request::create($url), $pageModel);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame($contentType, $response->headers->get('content-type'));
    }

    public static function getXMLFeedFormats(): iterable
    {
        yield 'RSS' => ['rss', '.xml', 'https://example.org/next-events.xml', 'application/rss+xml'];
        yield 'Atom' => ['atom', '.xml', 'https://example.org/next-events.xml', 'application/atom+xml'];
    }

    public static function getJSONFeedFormats(): iterable
    {
        yield 'JSON' => ['json', '.json', 'https://example.org/next-events.json', 'application/feed+json'];
    }

    private function getController(): CalendarFeedController
    {
        $contaoContext = $this->createMock(ContaoContext::class);
        $specification = new Specification(new NullLogger());

        return new CalendarFeedController($contaoContext, $specification, 'UTF-8');
    }

    private function getEvents(): array
    {
        return [
            [
                [
                    [
                        'id' => 1,
                        'pid' => 1,
                        'title' => 'Example title',
                    ],
                ],
            ],
        ];
    }
}
