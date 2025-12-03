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
use Contao\CoreBundle\Cache\CacheTagManager;
use Contao\CoreBundle\Config\ResourceFinder;
use Contao\CoreBundle\Controller\FrontendModule\FeedReaderController;
use Contao\CoreBundle\Pagination\PaginationConfig;
use Contao\CoreBundle\Pagination\PaginationFactoryInterface;
use Contao\CoreBundle\Pagination\PaginationInterface;
use Contao\CoreBundle\Routing\ScopeMatcher;
use Contao\CoreBundle\Tests\TestCase;
use Contao\CoreBundle\Twig\Interop\ContextFactory;
use Contao\CoreBundle\Twig\Loader\ContaoFilesystemLoader;
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
use Psr\Log\NullLogger;
use Symfony\Component\Cache\Adapter\NullAdapter;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Fragment\FragmentHandler;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment as TwigEnvironment;

class FeedReaderControllerTest extends TestCase
{
    private ContainerBuilder $container;

    protected function setUp(): void
    {
        parent::setUp();

        $this->container = $this->getContainerWithContaoConfiguration();
        $this->container->set('contao.cache.tag_manager', $this->createMock(CacheTagManager::class));
        $this->container->set('monolog.logger.contao.error', new NullLogger());
        $this->container->set('fragment.handler', $this->createMock(FragmentHandler::class));

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
            ->willReturn(new Result($this->createMock(Document::class), $feed, modifiedSince: new \DateTime(), response: $this->createMock(ResponseInterface::class), url: $feedUrl))
        ;

        $cache = new NullAdapter();

        $model = $this->mockClassWithProperties(ModuleModel::class, [
            'id' => 42,
            'rss_feed' => $feedUrl,
            'rss_cache' => 3600,
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
            'rss_feed' => $feedUrl,
            'rss_cache' => 3600,
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
            'rss_feed' => $feedUrl,
            'rss_cache' => 3600,
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
        $GLOBALS['TL_LANG']['MSC'] = [
            'first' => '',
            'next' => '',
            'previous' => '',
            'last' => '',
            'totalPages' => '',
            'pagination' => '',
            'goToPage' => '',
        ];

        $feedUrl = 'htts://example.org/feed';
        $feed = $this->getDummyFeed();

        $feedIo = $this->createMock(FeedIo::class);
        $feedIo
            ->expects($this->once())
            ->method('read')
            ->with($feedUrl, new Feed())
            ->willReturn(new Result($this->createMock(Document::class), $feed, modifiedSince: new \DateTime(), response: $this->createMock(ResponseInterface::class), url: $feedUrl))
        ;

        $cache = new NullAdapter();

        $model = $this->mockClassWithProperties(ModuleModel::class, [
            'id' => 42,
            'rss_feed' => $feedUrl,
            'rss_cache' => 3600,
            'skipFirst' => 0,
            'numberOfItems' => 0,
            'perPage' => 1,
        ]);

        $request = new Request(['page_r42' => 2], [], ['_scope' => 'frontend']);
        $requestStack = new RequestStack();
        $requestStack->push($request);

        $assertTwigContext = function (array $context) {
            $this->assertCount(1, $context['elements']);
            $this->assertNotEmpty($context['pagination']);

            return true;
        };

        $finder = new ResourceFinder(__DIR__.'/../../../contao');

        $container = $this->mockContainer(null, $assertTwigContext);
        $container->set('contao.resource_finder', $finder);

        $pagination = $this->createMock(PaginationInterface::class);
        $pagination
            ->expects($this->once())
            ->method('getItemsForPage')
            ->willReturn(['dummy'])
        ;

        $paginationFactory = $this->createMock(PaginationFactoryInterface::class);
        $paginationFactory
            ->expects($this->once())
            ->method('create')
            ->with(new PaginationConfig('page_r42', 2, 1))
            ->willReturn($pagination)
        ;

        $controller = $this->getController($feedIo, $cache, $requestStack, null, $container, $paginationFactory);
        $response = $controller($request, $model, 'main');

        $this->assertSame('rendered frontend_module/feed_reader', $response->getContent());
    }

    public function testDoesNotUsePagination(): void
    {
        $GLOBALS['TL_LANG']['MSC'] = [
            'first' => '',
            'next' => '',
            'previous' => '',
            'last' => '',
            'totalPages' => '',
            'pagination' => '',
            'goToPage' => '',
        ];

        $feedUrl = 'htts://example.org/feed';
        $feed = $this->getDummyFeed();

        $feedIo = $this->createMock(FeedIo::class);
        $feedIo
            ->expects($this->once())
            ->method('read')
            ->with($feedUrl, new Feed())
            ->willReturn(new Result($this->createMock(Document::class), $feed, modifiedSince: new \DateTime(), response: $this->createMock(ResponseInterface::class), url: $feedUrl))
        ;

        $cache = new NullAdapter();

        $model = $this->mockClassWithProperties(ModuleModel::class, [
            'id' => 42,
            'rss_feed' => $feedUrl,
            'rss_cache' => 3600,
            'skipFirst' => 0,
            'numberOfItems' => 0,
            'perPage' => 0,
        ]);

        $request = new Request(['page_r42' => 2], [], ['_scope' => 'frontend']);
        $requestStack = new RequestStack();
        $requestStack->push($request);

        $assertTwigContext = function (array $context) {
            $this->assertCount(2, $context['elements']);
            $this->assertEmpty($context['pagination']);

            return true;
        };

        $finder = new ResourceFinder(__DIR__.'/../../../contao');

        $container = $this->mockContainer(null, $assertTwigContext);
        $container->set('contao.resource_finder', $finder);

        $paginationFactory = $this->createMock(PaginationFactoryInterface::class);
        $paginationFactory
            ->expects($this->never())
            ->method('create')
        ;

        $controller = $this->getController($feedIo, $cache, $requestStack, null, $container, $paginationFactory);
        $response = $controller($request, $model, 'main');

        $this->assertSame('rendered frontend_module/feed_reader', $response->getContent());
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

    private function getController(FeedIo $feedIo, CacheInterface $cache, RequestStack $requestStack, LoggerInterface|null $logger = null, ContainerInterface|null $container = null, PaginationFactoryInterface|null $paginationFactory = null): FeedReaderController
    {
        $logger ??= $this->createMock(LoggerInterface::class);

        $paginationFactory ??= $this->createMock(PaginationFactoryInterface::class);

        $controller = new FeedReaderController($feedIo, $logger, $cache, $paginationFactory);
        $controller->setFragmentOptions(['template' => 'frontend_module/feed_reader']);
        $controller->setContainer($container ?? $this->mockContainer($requestStack));

        return $controller;
    }

    private function mockContainer(RequestStack|null $requestStack = null, callable|null $assertTwigContext = null): ContainerBuilder
    {
        $loader = $this->createMock(ContaoFilesystemLoader::class);
        $loader
            ->method('exists')
            ->willReturn(true)
        ;

        $loader
            ->method('getFirst')
            ->willReturnCallback(
                static fn (string $identifier): string => 'pagination' === $identifier ? 'templates/pagination.html5' : "path/to/$identifier.html.twig",
            )
        ;

        $twig = $this->createMock(TwigEnvironment::class);

        if ($assertTwigContext) {
            $twig
                ->method('render')
                ->with(
                    '@Contao/frontend_module/feed_reader.html.twig',
                    $this->callback($assertTwigContext),
                )
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
        $this->container->set('contao.twig.interop.context_factory', new ContextFactory($this->createMock(ScopeMatcher::class)));
        $this->container->set('twig', $twig);
        $this->container->set('contao.framework', $this->mockContaoFramework());
        $this->container->set('contao.routing.scope_matcher', $this->mockScopeMatcher());
        $this->container->set('cache.system', $this->createMock(CacheInterface::class));
        $this->container->set('translator', $this->createMock(TranslatorInterface::class));

        if ($requestStack instanceof RequestStack) {
            $this->container->set('request_stack', $requestStack);
        }

        System::setContainer($this->container);

        return $this->container;
    }
}
