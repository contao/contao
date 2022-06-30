<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Controller\Page;

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

        $this->assertSame([
            '.xml',
            '.json',
        ], $controller->getUrlSuffixes());
    }

    /**
     * @dataProvider getFeedFormats
     */
    public function testConfiguresPageRoute(string $format, string $suffix): void
    {
        $pageModel = $this->mockClassWithProperties(PageModel::class, [
            'id' => 42,
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
            'alias' => 'latest-news',
            'feedFormat' => 'foo',
        ]);

        $this->expectException(\RuntimeException::class);

        $route = new PageRoute($pageModel);
        $controller = $this->getController();
        $controller->configurePageRoute($route);
    }

    public function testReturnsEmptyFeed(): void
    {
        $pageModel = $this->mockClassWithProperties(PageModel::class, [
            'id' => 42,
            'title' => 'Latest News',
            'description' => 'Get latest news',
            'alias' => 'latest-news',
            'feedFormat' => 'rss',
            'language' => 'en',
        ]);

        $container = $this->getContainerWithContaoConfiguration();
        $container->set('contao.framework', $this->mockContaoFramework());
        $container->set('event_dispatcher', $this->createMock(EventDispatcher::class));

        $request = Request::create('https://example.org/latest-news.xml');
        $controller = $this->getController();
        $controller->setContainer($container);
        $response = $controller($request, $pageModel);
        $this->assertSame(204, $response->getStatusCode());
    }

    /**
     * @dataProvider getFeedFormats
     */
    public function testReturnsFeedInCorrectFormat(string $format, string $suffix, string $url, string $contentType): void
    {
        $pageModel = $this->mockClassWithProperties(PageModel::class, [
            'id' => 42,
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
                }
            )
        ;
        $container->set('event_dispatcher', $dispatcher);

        $controller = $this->getController();
        $controller->setContainer($container);
        $response = $controller(Request::create($url), $pageModel);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame($contentType, $response->headers->get('content-type'));
    }

    public function getFeedFormats(): \Generator
    {
        yield 'RSS' => ['rss', '.xml', 'https://example.org/latest-news.xml', 'application/rss+xml'];
        yield 'Atom' => ['atom', '.xml', 'https://example.org/latest-news.xml', 'application/atom+xml'];
        yield 'JSON' => ['json', '.json', 'https://example.org/latest-news.json', 'application/feed+json'];
    }

    private function getController(): NewsFeedController
    {
        $contaoContext = $this->createMock(ContaoContext::class);
        $specification = new Specification(new NullLogger());

        return new NewsFeedController($contaoContext, $specification);
    }

    private function getArticlesAsArray(int $count = 1)
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
