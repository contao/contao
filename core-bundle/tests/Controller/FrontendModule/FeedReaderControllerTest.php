<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Controller\FrontendModule;

use Contao\Config;
use Contao\CoreBundle\Cache\EntityCacheTags;
use Contao\CoreBundle\Config\ResourceFinder;
use Contao\CoreBundle\Controller\FrontendModule\FeedReaderController;
use Contao\CoreBundle\Exception\PageNotFoundException;
use Contao\CoreBundle\Tests\TestCase;
use Contao\CoreBundle\Twig\Interop\ContextFactory;
use Contao\Environment;
use Contao\Input;
use Contao\ModuleModel;
use Contao\System;
use Contao\TemplateLoader;
use FeedIo\Adapter\ResponseInterface;
use FeedIo\Feed;
use FeedIo\FeedInterface;
use FeedIo\FeedIo;
use FeedIo\Reader\Document;
use FeedIo\Reader\Result;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Cache\Adapter\NullAdapter;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Contracts\Cache\CacheInterface;
use Twig\Environment as TwigEnvironment;
use Twig\Loader\LoaderInterface;

class FeedReaderControllerTest extends TestCase
{
    private ContainerBuilder $container;

    protected function setUp(): void
    {
        parent::setUp();

        $this->container = $this->getContainerWithContaoConfiguration();
        $this->container->set('contao.cache.entity_tags', $this->createMock(EntityCacheTags::class));

        System::setContainer($this->container);
    }

    protected function tearDown(): void
    {
        unset($GLOBALS['TL_MIME'], $GLOBALS['TL_LANG']);

        $this->resetStaticProperties([TemplateLoader::class, System::class, Config::class]);

        parent::tearDown();
    }

    public function testFetchesFeedFromSourceAndRendersTemplate(): void
    {
        $feedUrl = 'htts://example.org/feed';
        $feed = $this->getDummyFeed();

        $feedIo = $this->createMock(FeedIo::class);
        $feedIo
            ->expects($this->once())
            ->method('read')
            ->with($feedUrl, new Feed())
            ->willReturn(new Result($this->createMock(Document::class), $feed, new \DateTime(), $this->createMock(ResponseInterface::class), $feedUrl))
        ;

        $cache = new NullAdapter();

        $model = $this->mockClassWithProperties(ModuleModel::class, [
            'id' => 42,
            'feed_urls' => $feedUrl,
            'feed_cache' => 3600,
            'skipFirst' => 0,
            'numberOfItems' => 0,
        ]);

        $request = new Request([], [], ['_scope' => 'frontend']);
        $requestStack = new RequestStack();
        $requestStack->push($request);

        $controller = $this->getController($feedIo, $cache, $requestStack);
        $response = $controller($request, $model, 'main');

        $this->assertSame('rendered frontend_module/feed_reader', $response->getContent());
    }

    public function testFetchesFeedFromCacheAndRendersTemplate(): void
    {
        $feedUrl = 'htts://example.org/feed';
        $cacheKey = 'feed_reader_42_'.md5($feedUrl);
        $feed = $this->getDummyFeed();

        $feedIo = $this->createMock(FeedIo::class);
        $feedIo
            ->expects($this->never())
            ->method('read')
        ;

        $cache = $this->createMock(CacheInterface::class);
        $cache
            ->expects($this->once())
            ->method('get')
            ->with($cacheKey)
            ->willReturn($feed)
        ;

        $model = $this->mockClassWithProperties(ModuleModel::class, [
            'id' => 42,
            'feed_urls' => $feedUrl,
            'feed_cache' => 3600,
            'skipFirst' => 0,
            'numberOfItems' => 0,
        ]);

        $request = new Request([], [], ['_scope' => 'frontend']);
        $requestStack = new RequestStack();
        $requestStack->push($request);

        $controller = $this->getController($feedIo, $cache, $requestStack);
        $response = $controller($request, $model, 'main');

        $this->assertSame('rendered frontend_module/feed_reader', $response->getContent());
    }

    public function testHandlesExceptionWhileReadingFeedGracefully(): void
    {
        $feedUrl = 'htts://example.org/feed';

        $feedIo = $this->createMock(FeedIo::class);
        $feedIo
            ->expects($this->once())
            ->method('read')
            ->with($feedUrl, new Feed())
            ->willThrowException(new \Exception('Feed not parsable'))
        ;

        $cache = new NullAdapter();

        $model = $this->mockClassWithProperties(ModuleModel::class, [
            'id' => 42,
            'feed_urls' => $feedUrl,
            'feed_cache' => 3600,
            'skipFirst' => 0,
            'numberOfItems' => 0,
        ]);

        $request = new Request([], [], ['_scope' => 'frontend']);
        $requestStack = new RequestStack();
        $requestStack->push($request);

        $logger = $this->createMock(LoggerInterface::class);
        $logger
            ->expects($this->once())
            ->method('error')
            ->with('Could not read feed htts://example.org/feed: Feed not parsable')
        ;

        $controller = $this->getController($feedIo, $cache, $requestStack, $logger);
        $response = $controller($request, $model, 'main');

        $this->assertSame('rendered frontend_module/feed_reader', $response->getContent());
    }

    public function testHandlesPaginatedList(): void
    {
        $GLOBALS['TL_LANG']['MSC'] = ['first' => '', 'next' => '', 'previous' => '', 'last' => '', 'totalPages' => '', 'pagination' => '', 'goToPage' => ''];

        $feedUrl = 'htts://example.org/feed';
        $feed = $this->getDummyFeed();

        $feedIo = $this->createMock(FeedIo::class);
        $feedIo
            ->expects($this->once())
            ->method('read')
            ->with($feedUrl, new Feed())
            ->willReturn(new Result($this->createMock(Document::class), $feed, new \DateTime(), $this->createMock(ResponseInterface::class), $feedUrl))
        ;

        $cache = new NullAdapter();

        $model = $this->mockClassWithProperties(ModuleModel::class, [
            'id' => 42,
            'feed_urls' => $feedUrl,
            'feed_cache' => 3600,
            'skipFirst' => 0,
            'numberOfItems' => 0,
            'perPage' => 1,
        ]);

        $request = new Request(['page_r42' => 2], [], ['_scope' => 'frontend']);
        $requestStack = new RequestStack();
        $requestStack->push($request);

        $assertTwigContext = function (array $context) {
            $this->assertCount(1, $context['items']);
            $this->assertNotEmpty($context['pagination']);

            return true;
        };

        $finder = new ResourceFinder(__DIR__.'/../../../src/Resources/contao');

        $container = $this->mockContainer(null, $assertTwigContext);
        $container->set('contao.resource_finder', $finder);

        $controller = $this->getController($feedIo, $cache, $requestStack, null, $container);
        $response = $controller($request, $model, 'main');

        $this->assertSame('rendered frontend_module/feed_reader', $response->getContent());
    }

    public function testThrowExceptionIfRequestedPageIsOutOfBounds(): void
    {
        $GLOBALS['TL_LANG']['MSC'] = ['first' => '', 'next' => '', 'previous' => '', 'last' => '', 'totalPages' => '', 'pagination' => '', 'goToPage' => ''];

        $feedUrl = 'htts://example.org/feed';
        $feed = $this->getDummyFeed();

        $feedIo = $this->createMock(FeedIo::class);
        $feedIo
            ->expects($this->once())
            ->method('read')
            ->with($feedUrl, new Feed())
            ->willReturn(new Result($this->createMock(Document::class), $feed, new \DateTime(), $this->createMock(ResponseInterface::class), $feedUrl))
        ;

        $cache = new NullAdapter();

        $model = $this->mockClassWithProperties(ModuleModel::class, [
            'id' => 42,
            'feed_urls' => $feedUrl,
            'feed_cache' => 3600,
            'skipFirst' => 0,
            'numberOfItems' => 0,
            'perPage' => 1,
        ]);

        $request = new Request(['page_r42' => 3], [], ['_scope' => 'frontend']);
        $requestStack = new RequestStack();
        $requestStack->push($request);

        $this->expectException(PageNotFoundException::class);

        $controller = $this->getController($feedIo, $cache, $requestStack);
        $controller($request, $model, 'main');
    }

    private function getDummyFeed(): FeedInterface
    {
        $feed = new Feed();

        $feed->setTitle('Example');
        $feed->setDescription('This is an example feed');

        $item = $feed->newItem();
        $item->setTitle('Example item');
        $item->setContent('Example content');
        $feed->add($item);

        $item = $feed->newItem();
        $item->setTitle('Example item 2');
        $item->setContent('Example content 2');
        $feed->add($item);

        return $feed;
    }

    private function getController(FeedIo $feedIo, CacheInterface $cache, RequestStack $requestStack, LoggerInterface $logger = null, ContainerInterface $container = null): FeedReaderController
    {
        $logger = $logger ?? $this->createMock(LoggerInterface::class);

        $controller = new FeedReaderController($feedIo, $logger, $cache);
        $controller->setFragmentOptions(['template' => 'frontend_module/feed_reader']);
        $controller->setContainer($container ?? $this->mockContainer($requestStack));

        return $controller;
    }

    private function mockContainer(RequestStack $requestStack = null, callable $assertTwigContext = null): ContainerBuilder
    {
        $loader = $this->createMock(LoaderInterface::class);
        $loader
            ->method('exists')
            ->with('@Contao/frontend_module/feed_reader.html.twig')
            ->willReturn(true)
        ;

        $twig = $this->createMock(TwigEnvironment::class);

        if ($assertTwigContext) {
            $twig
                ->method('render')
                ->with('@Contao/frontend_module/feed_reader.html.twig', $this->callback($assertTwigContext))
                ->willReturn('rendered frontend_module/feed_reader')
            ;
        } else {
            $twig
                ->method('render')
                ->with('@Contao/frontend_module/feed_reader.html.twig')
                ->willReturn('rendered frontend_module/feed_reader')
            ;
        }

        $this->container->set('contao.twig.filesystem_loader', $loader);
        $this->container->set('contao.twig.interop.context_factory', new ContextFactory());
        $this->container->set('twig', $twig);

        $configAdapter = $this->mockAdapter(['get']);
        $configAdapter
            ->method('get')
            ->with('maxPaginationLinks')
            ->willReturn(7)
        ;

        $environmentAdapter = $this->mockAdapter(['get']);
        $environmentAdapter
            ->method('get')
            ->willReturnMap([
                ['requestUri', ''],
                ['queryString', ''],
            ])
        ;

        $framework = $this->mockContaoFramework([Config::class => $configAdapter, Environment::class => $environmentAdapter, Input::class => $this->mockAdapter(['get'])]);

        $this->container->set('contao.framework', $framework);
        $this->container->set('contao.routing.scope_matcher', $this->mockScopeMatcher());
        $this->container->set('cache.system', $this->createMock(CacheInterface::class));

        if ($requestStack instanceof RequestStack) {
            $this->container->set('request_stack', $requestStack);
        }

        System::setContainer($this->container);

        return $this->container;
    }
}
