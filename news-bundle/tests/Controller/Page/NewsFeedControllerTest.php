<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\NewsBundle\Tests\Controller\Page;

use Contao\CoreBundle\Asset\ContaoContext;
use Contao\CoreBundle\Cache\EntityCacheTags;
use Contao\CoreBundle\Routing\Page\PageRoute;
use Contao\NewsBundle\Controller\Page\NewsFeedController;
use Contao\NewsBundle\Event\FetchArticlesForFeedEvent;
use Contao\NewsBundle\Event\TransformArticleForFeedEvent;
use Contao\NewsModel;
use Contao\PageModel;
use Contao\System;
use Contao\TestCase\ContaoTestCase;
use FeedIo\Feed\Item;
use FeedIo\Specification;
use Psr\Log\NullLogger;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpFoundation\Request;

class NewsFeedControllerTest extends ContaoTestCase
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

    /**
     * @dataProvider getXMLFeedFormats
     * @dataProvider getJSONFeedFormats
     */
    public function testConfiguresPageRoute(string $format, string $suffix): void
    {
        $pageModel = $this->mockClassWithProperties(PageModel::class, [
            'id' => 42,
            'title' => 'Latest News',
            'alias' => 'latest-news',
            'feedFormat' => $format,
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
            'title' => 'Latest News',
            'alias' => 'latest-news',
            'feedFormat' => 'foo',
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
            'title' => 'Latest News',
            'alias' => 'latest-news',
            'feedDescription' => 'Get latest news',
            'feedFormat' => 'rss',
            'language' => 'en',
        ]);

        $container = $this->getContainerWithContaoConfiguration();
        $container->set('contao.framework', $this->mockContaoFramework());
        $container->set('event_dispatcher', $this->createMock(EventDispatcher::class));

        $controller = $this->getController();
        $controller->setContainer($container);

        $request = Request::create('https://example.org/latest-news.xml');
        $response = $controller($request, $pageModel);

        $this->assertSame(200, $response->getStatusCode());
    }

    /**
     * @dataProvider getXMLFeedFormats
     */
    public function testProperlyEncodesXMLEntities(string $format): void
    {
        $pageModel = $this->mockClassWithProperties(PageModel::class, [
            'id' => 42,
            'title' => 'Latest News &lt;/channel&gt;',
            'alias' => 'latest-news',
            'feedDescription' => 'Get latest news &lt;/channel&gt;',
            'feedFormat' => $format,
            'language' => 'en',
        ]);

        $container = $this->getContainerWithContaoConfiguration();
        $container->set('contao.framework', $this->mockContaoFramework());
        $container->set('event_dispatcher', $this->createMock(EventDispatcher::class));

        $controller = $this->getController();
        $controller->setContainer($container);

        $request = Request::create('https://example.org/latest-news.xml');
        $response = $controller($request, $pageModel);

        $this->assertSame(200, $response->getStatusCode());

        $document = new \DOMDocument('1.0', 'utf-8');
        $document->loadXML($response->getContent());

        $this->assertSame('Latest News </channel>', $document->getElementsByTagName('title')->item(0)->textContent);
        $this->assertSame('Get latest news </channel>', $document->getElementsByTagName('description')->item(0)->textContent);
    }

    /**
     * @dataProvider getXMLFeedFormats
     * @dataProvider getJSONFeedFormats
     */
    public function testReturnsFeedInCorrectFormat(string $format, string $suffix, string $url, string $contentType): void
    {
        $pageModel = $this->mockClassWithProperties(PageModel::class, [
            'id' => 42,
            'title' => 'Latest News',
            'alias' => 'latest-news',
            'feedFormat' => $format,
        ]);

        $container = $this->getContainerWithContaoConfiguration();
        $container->set('contao.framework', $this->mockContaoFramework());
        $container->set('contao.cache.entity_tags', $this->createMock(EntityCacheTags::class));

        $dispatcher = $this->createMock(EventDispatcher::class);
        $dispatcher
            ->method('dispatch')
            ->willReturnCallback(
                function ($event) {
                    if ($event instanceof FetchArticlesForFeedEvent) {
                        $event->setArticles($this->getArticlesAsArray(3));

                        return $event;
                    }

                    if ($event instanceof TransformArticleForFeedEvent) {
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

    public function getXMLFeedFormats(): \Generator
    {
        yield 'RSS' => ['rss', '.xml', 'https://example.org/latest-news.xml', 'application/rss+xml'];
        yield 'Atom' => ['atom', '.xml', 'https://example.org/latest-news.xml', 'application/atom+xml'];
    }

    public function getJSONFeedFormats(): \Generator
    {
        yield 'JSON' => ['json', '.json', 'https://example.org/latest-news.json', 'application/feed+json'];
    }

    private function getController(): NewsFeedController
    {
        $contaoContext = $this->createMock(ContaoContext::class);
        $specification = new Specification(new NullLogger());

        return new NewsFeedController($contaoContext, $specification, 'UTF-8');
    }

    private function getArticlesAsArray(int $count = 1): array
    {
        $articles = [];

        for ($i = 0; $i < $count; ++$i) {
            $articles[] = $this->mockClassWithProperties(NewsModel::class, [
                'id' => 1 + $i,
                'pid' => 1 + $i,
                'title' => 'Example title',
            ]);
        }

        return $articles;
    }
}
