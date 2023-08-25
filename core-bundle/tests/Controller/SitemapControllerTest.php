<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Controller;

use Contao\ArticleModel;
use Contao\CoreBundle\Cache\EntityCacheTags;
use Contao\CoreBundle\Controller\SitemapController;
use Contao\CoreBundle\Event\SitemapEvent;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Routing\Page\PageRegistry;
use Contao\CoreBundle\Routing\Page\RouteConfig;
use Contao\CoreBundle\Routing\PageFinder;
use Contao\CoreBundle\Security\ContaoCorePermissions;
use Contao\CoreBundle\Tests\TestCase;
use Contao\PageModel;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Exception\RuntimeException;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class SitemapControllerTest extends TestCase
{
    public function testThrowsNotFoundHttpExceptionIfNoRootPageFound(): void
    {
        $eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $eventDispatcher
            ->expects($this->never())
            ->method('dispatch')
        ;

        $request = Request::create('https://www.foobar.com/sitemap.xml');

        $pageFinder = $this->createMock(PageFinder::class);
        $pageFinder
            ->expects($this->once())
            ->method('findRootPagesForHost')
            ->with($request->getHost())
            ->willReturn([])
        ;

        $container = $this->getContainerWithContaoConfiguration();
        $container->set('event_dispatcher', $eventDispatcher);

        $registry = new PageRegistry($this->createMock(Connection::class));

        $this->expectException(NotFoundHttpException::class);

        $controller = new SitemapController($registry, $pageFinder);
        $controller->setContainer($container);
        $controller($request);
    }

    public function testIgnoresRequestPort(): void
    {
        $page1 = $this->mockPage(
            [
                'id' => 43,
                'pid' => 42,
                'type' => 'regular',
                'groups' => [],
                'published' => true,
                'rootLanguage' => 'en',
            ],
            ['' => 'https://www.foobar.com:8000/en/page1.html'],
        );

        $framework = $this->mockFrameworkWithPages([42 => [$page1], 43 => null, 21 => null], [43 => null]);
        $container = $this->getContainer($framework, null, 'https://www.foobar.com:8000');
        $registry = new PageRegistry($this->createMock(Connection::class));
        $request = Request::create('https://www.foobar.com:8000/sitemap.xml');

        $pageFinder = $this->mockPageFinder($request);

        $controller = new SitemapController($registry, $pageFinder);
        $controller->setContainer($container);

        $response = $controller($request);

        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $this->assertSame('public, s-maxage=2592000', $response->headers->get('Cache-Control'));
        $this->assertSame($this->getExpectedSitemapContent(['https://www.foobar.com:8000/en/page1.html']), $response->getContent());
    }

    public function testGeneratesSitemapForRegularPages(): void
    {
        $page1 = $this->mockPage(
            [
                'id' => 43,
                'pid' => 42,
                'type' => 'regular',
                'groups' => [],
                'published' => true,
                'rootLanguage' => 'en',
            ],
            ['' => 'https://www.foobar.com/en/page1.html'],
        );

        $framework = $this->mockFrameworkWithPages([42 => [$page1], 43 => null, 21 => null], [43 => null]);
        $container = $this->getContainer($framework);
        $registry = new PageRegistry($this->createMock(Connection::class));
        $request = Request::create('https://www.foobar.com/sitemap.xml');

        $controller = new SitemapController($registry, $this->mockPageFinder($request));
        $controller->setContainer($container);

        $response = $controller($request);

        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $this->assertSame('public, s-maxage=2592000', $response->headers->get('Cache-Control'));
        $this->assertSame($this->getExpectedSitemapContent(['https://www.foobar.com/en/page1.html']), $response->getContent());
    }

    public function testRecursivelyWalksThePageTree(): void
    {
        $page1 = $this->mockPage(
            [
                'id' => 43,
                'pid' => 42,
                'type' => 'regular',
                'groups' => [],
                'published' => true,
                'rootLanguage' => 'en',
            ],
            ['' => 'https://www.foobar.com/en/page1.html'],
        );

        $page2 = $this->mockPage(
            [
                'id' => 44,
                'pid' => 43,
                'type' => 'regular',
                'groups' => [],
                'published' => true,
                'rootLanguage' => 'en',
            ],
            ['' => 'https://www.foobar.com/en/page2.html'],
        );

        $pages = [
            42 => [$page1],
            43 => [$page2],
            44 => null,
            21 => null,
        ];

        $framework = $this->mockFrameworkWithPages($pages, [43 => null, 44 => null]);
        $container = $this->getContainer($framework);
        $registry = new PageRegistry($this->createMock(Connection::class));
        $request = Request::create('https://www.foobar.com/sitemap.xml');

        $controller = new SitemapController($registry, $this->mockPageFinder($request));
        $controller->setContainer($container);

        $response = $controller($request);

        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $this->assertSame('public, s-maxage=2592000', $response->headers->get('Cache-Control'));

        $expectedUrls = [
            'https://www.foobar.com/en/page1.html',
            'https://www.foobar.com/en/page2.html',
        ];

        $this->assertSame($this->getExpectedSitemapContent($expectedUrls), $response->getContent());
    }

    public function testSkipsUnpublishedPagesButAddsChildPages(): void
    {
        $page1 = $this->mockPage([
            'id' => 43,
            'pid' => 42,
            'type' => 'regular',
            'groups' => [],
            'published' => false,
            'rootLanguage' => 'en',
        ]);

        $page2 = $this->mockPage(
            [
                'id' => 44,
                'pid' => 43,
                'type' => 'regular',
                'groups' => [],
                'published' => true,
                'rootLanguage' => 'en',
            ],
            ['' => 'https://www.foobar.com/en/page2.html'],
        );

        $pages = [
            42 => [$page1],
            43 => [$page2],
            44 => null,
            21 => null,
        ];

        // Only find articles for published page
        $articles = [44 => null];

        $framework = $this->mockFrameworkWithPages($pages, $articles);
        $container = $this->getContainer($framework);
        $registry = new PageRegistry($this->createMock(Connection::class));
        $request = Request::create('https://www.foobar.com/sitemap.xml');

        $controller = new SitemapController($registry, $this->mockPageFinder($request));
        $controller->setContainer($container);

        $response = $controller($request);

        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $this->assertSame('public, s-maxage=2592000', $response->headers->get('Cache-Control'));
        $this->assertSame($this->getExpectedSitemapContent(['https://www.foobar.com/en/page2.html']), $response->getContent());
    }

    public function testSkipsPagesThatRequireItemButAddsChildPages(): void
    {
        $page1 = $this->mockPage([
            'id' => 43,
            'pid' => 42,
            'type' => 'regular',
            'groups' => [],
            'published' => true,
            'requireItem' => true,
            'rootLanguage' => 'en',
        ]);

        $page2 = $this->mockPage(
            [
                'id' => 44,
                'pid' => 43,
                'type' => 'regular',
                'groups' => [],
                'published' => true,
                'rootLanguage' => 'en',
            ],
            ['' => 'https://www.foobar.com/en/page2.html'],
        );

        $pages = [
            42 => [$page1],
            43 => [$page2],
            44 => null,
            21 => null,
        ];

        // Only find articles for published page
        $articles = [44 => null];

        $framework = $this->mockFrameworkWithPages($pages, $articles);
        $container = $this->getContainer($framework);
        $registry = new PageRegistry($this->createMock(Connection::class));
        $request = Request::create('https://www.foobar.com/sitemap.xml');

        $controller = new SitemapController($registry, $this->mockPageFinder($request));
        $controller->setContainer($container);

        $response = $controller($request);

        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $this->assertSame('public, s-maxage=2592000', $response->headers->get('Cache-Control'));
        $this->assertSame($this->getExpectedSitemapContent(['https://www.foobar.com/en/page2.html']), $response->getContent());
    }

    public function testSkipsPagesWithoutContentComposition(): void
    {
        $page1 = $this->mockPage([
            'id' => 43,
            'pid' => 42,
            'type' => 'forward',
            'groups' => [],
            'published' => true,
            'rootLanguage' => 'en',
        ]);

        $page2 = $this->mockPage(
            [
                'id' => 44,
                'pid' => 43,
                'type' => 'regular',
                'groups' => [],
                'published' => true,
                'rootLanguage' => 'en',
            ],
            ['' => 'https://www.foobar.com/en/page2.html'],
        );

        $pages = [
            42 => [$page1],
            43 => [$page2],
            44 => null,
            21 => null,
        ];

        // Only find articles for published page
        $articles = [44 => null];

        $framework = $this->mockFrameworkWithPages($pages, $articles);
        $container = $this->getContainer($framework);
        $request = Request::create('https://www.foobar.com/sitemap.xml');

        $registry = $this->createPartialMock(PageRegistry::class, ['isRoutable', 'supportsContentComposition']);
        $registry
            ->expects($this->exactly(2))
            ->method('supportsContentComposition')
            ->withConsecutive([$page1], [$page2])
            ->willReturn(false, true)
        ;

        $registry
            ->expects($this->once())
            ->method('isRoutable')
            ->withConsecutive([$page1])
            ->willReturn(true)
        ;

        $controller = new SitemapController($registry, $this->mockPageFinder($request));
        $controller->setContainer($container);

        $response = $controller($request);

        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $this->assertSame('public, s-maxage=2592000', $response->headers->get('Cache-Control'));
        $this->assertSame($this->getExpectedSitemapContent(['https://www.foobar.com/en/page2.html']), $response->getContent());
    }

    public function testSkipsUnroutablePages(): void
    {
        $page1 = $this->mockPage([
            'id' => 43,
            'pid' => 42,
            'type' => 'error_404',
            'groups' => [],
            'published' => true,
            'rootLanguage' => 'en',
        ]);

        $page2 = $this->mockPage(
            [
                'id' => 44,
                'pid' => 43,
                'type' => 'regular',
                'groups' => [],
                'published' => true,
                'rootLanguage' => 'en',
            ],
            ['' => 'https://www.foobar.com/en/page2.html'],
        );

        $pages = [
            42 => [$page1],
            43 => [$page2],
            44 => null,
            21 => null,
        ];

        $framework = $this->mockFrameworkWithPages($pages, [44 => null]);
        $container = $this->getContainer($framework);
        $request = Request::create('https://www.foobar.com/sitemap.xml');

        $registry = $this->createPartialMock(PageRegistry::class, ['isRoutable', 'supportsContentComposition']);
        $registry
            ->expects($this->exactly(2))
            ->method('supportsContentComposition')
            ->withConsecutive([$page1], [$page2])
            ->willReturn(true, true)
        ;

        $registry
            ->expects($this->exactly(2))
            ->method('isRoutable')
            ->withConsecutive([$page1], [$page2])
            ->willReturn(false, true)
        ;

        $controller = new SitemapController($registry, $this->mockPageFinder($request));
        $controller->setContainer($container);

        $response = $controller($request);

        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $this->assertSame('public, s-maxage=2592000', $response->headers->get('Cache-Control'));
        $this->assertSame($this->getExpectedSitemapContent(['https://www.foobar.com/en/page2.html']), $response->getContent());
    }

    public function testSkipsPagesWithRoutingException(): void
    {
        $page1 = $this->mockClassWithProperties(PageModel::class, [
            'id' => 43,
            'pid' => 42,
            'type' => 'error_404',
            'groups' => [],
            'published' => '1',
            'rootLanguage' => 'en',
        ]);

        $page1
            ->expects($this->once())
            ->method('getAbsoluteUrl')
            ->willThrowException(new RuntimeException())
        ;

        $page2 = $this->mockPage(
            [
                'id' => 44,
                'pid' => 43,
                'type' => 'regular',
                'groups' => [],
                'published' => '1',
                'rootLanguage' => 'en',
            ],
            ['' => 'https://www.foobar.com/en/page2.html'],
        );

        $pages = [
            42 => [$page1],
            43 => [$page2],
            44 => null,
            21 => null,
        ];

        $framework = $this->mockFrameworkWithPages($pages, [44 => null]);
        $container = $this->getContainer($framework);
        $request = Request::create('https://www.foobar.com/sitemap.xml');

        $registry = $this->createPartialMock(PageRegistry::class, ['isRoutable', 'supportsContentComposition']);
        $registry
            ->expects($this->exactly(2))
            ->method('supportsContentComposition')
            ->withConsecutive([$page1], [$page2])
            ->willReturn(true, true)
        ;

        $registry
            ->expects($this->exactly(2))
            ->method('isRoutable')
            ->withConsecutive([$page1], [$page2])
            ->willReturn(true, true)
        ;

        $controller = new SitemapController($registry, $this->mockPageFinder($request));
        $controller->setContainer($container);

        $response = $controller($request);

        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $this->assertSame('public, s-maxage=2592000', $response->headers->get('Cache-Control'));
        $this->assertSame($this->getExpectedSitemapContent(['https://www.foobar.com/en/page2.html']), $response->getContent());
    }

    public function testSkipsPagesIfTheUserDoesNotHaveAccess(): void
    {
        $page1 = $this->mockPage(
            [
                'id' => 43,
                'pid' => 42,
                'type' => 'regular',
                'protected' => false,
                'groups' => [],
                'published' => true,
                'rootLanguage' => 'en',
            ],
            ['' => 'https://www.foobar.com/en/page1.html'],
        );

        $page2 = $this->mockPage([
            'id' => 44,
            'pid' => 43,
            'type' => 'regular',
            'protected' => true,
            'groups' => [],
            'published' => true,
            'rootLanguage' => 'en',
        ]);

        $page3 = $this->mockPage(
            [
                'id' => 45,
                'pid' => 43,
                'type' => 'regular',
                'protected' => true,
                'groups' => ['2'],
                'published' => true,
                'rootLanguage' => 'en',
            ],
            ['' => 'https://www.foobar.com/en/page3.html'],
        );

        $pages = [
            42 => [$page1, $page2, $page3],
            43 => null,
            45 => null,
            21 => null,
        ];

        $framework = $this->mockFrameworkWithPages($pages, [43 => null, 45 => null]);
        $container = $this->getContainer($framework, [2]);
        $registry = new PageRegistry($this->createMock(Connection::class));
        $request = Request::create('https://www.foobar.com/sitemap.xml');

        $controller = new SitemapController($registry, $this->mockPageFinder($request));
        $controller->setContainer($container);

        $response = $controller($request);

        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $this->assertSame('public, s-maxage=2592000', $response->headers->get('Cache-Control'));

        $expectedUrls = [
            'https://www.foobar.com/en/page1.html',
            'https://www.foobar.com/en/page3.html',
        ];

        $this->assertSame($this->getExpectedSitemapContent($expectedUrls), $response->getContent());
    }

    public function testGeneratesTheTeaserArticleUrls(): void
    {
        $page1 = $this->mockPage(
            [
                'id' => 43,
                'pid' => 42,
                'type' => 'regular',
                'protected' => false,
                'groups' => [],
                'published' => true,
                'rootLanguage' => 'en',
            ],
            [
                '' => 'https://www.foobar.com/en/page1.html',
                '/articles/foobar' => 'https://www.foobar.com/en/page1/articles/foobar.html',
            ],
        );

        $article1 = $this->mockClassWithProperties(ArticleModel::class, [
            'id' => 1,
            'pid' => 43,
            'alias' => 'foobar',
        ]);

        $framework = $this->mockFrameworkWithPages([42 => [$page1], 43 => null, 21 => null], [43 => [$article1]]);
        $container = $this->getContainer($framework);
        $registry = new PageRegistry($this->createMock(Connection::class));
        $request = Request::create('https://www.foobar.com/sitemap.xml');

        $controller = new SitemapController($registry, $this->mockPageFinder($request));
        $controller->setContainer($container);

        $response = $controller($request);

        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $this->assertSame('public, s-maxage=2592000', $response->headers->get('Cache-Control'));
        $this->assertSame($this->getExpectedSitemapContent(['https://www.foobar.com/en/page1.html', 'https://www.foobar.com/en/page1/articles/foobar.html']), $response->getContent());
    }

    public function testGeneratesTheTeaserArticleUrlsByIdIfAliasIsEmpty(): void
    {
        $page1 = $this->mockPage(
            [
                'id' => 43,
                'pid' => 42,
                'type' => 'regular',
                'protected' => false,
                'groups' => [],
                'published' => true,
                'rootLanguage' => 'en',
            ],
            [
                '' => 'https://www.foobar.com/en/page1.html',
                '/articles/1' => 'https://www.foobar.com/en/page1/articles/1.html',
            ],
        );

        $article1 = $this->mockClassWithProperties(ArticleModel::class, [
            'id' => 1,
            'pid' => 43,
            'alias' => '',
        ]);

        $framework = $this->mockFrameworkWithPages([42 => [$page1], 43 => null, 21 => null], [43 => [$article1]]);
        $container = $this->getContainer($framework);
        $registry = new PageRegistry($this->createMock(Connection::class));
        $request = Request::create('https://www.foobar.com/sitemap.xml');

        $controller = new SitemapController($registry, $this->mockPageFinder($request));
        $controller->setContainer($container);

        $response = $controller($request);

        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $this->assertSame('public, s-maxage=2592000', $response->headers->get('Cache-Control'));
        $this->assertSame($this->getExpectedSitemapContent(['https://www.foobar.com/en/page1.html', 'https://www.foobar.com/en/page1/articles/1.html']), $response->getContent());
    }

    public function testSkipsNoindexNofollowPages(): void
    {
        $page1 = $this->mockPage(
            [
                'id' => 43,
                'pid' => 42,
                'type' => 'regular',
                'groups' => [],
                'published' => true,
                'robots' => 'index,follow',
                'rootLanguage' => 'en',
            ],
            ['' => 'https://www.foobar.com/en/page1.html'],
        );

        $page2 = $this->mockPage([
            'id' => 44,
            'pid' => 42,
            'type' => 'regular',
            'groups' => [],
            'published' => true,
            'robots' => 'noindex,nofollow',
            'rootLanguage' => 'en',
        ]);

        $pages = [
            42 => [$page1, $page2],
            43 => null,
            44 => null,
            21 => null,
        ];

        $framework = $this->mockFrameworkWithPages($pages, [43 => null]);
        $container = $this->getContainer($framework);
        $registry = new PageRegistry($this->createMock(Connection::class));
        $request = Request::create('https://www.foobar.com/sitemap.xml');

        $controller = new SitemapController($registry, $this->mockPageFinder($request));
        $controller->setContainer($container);

        $response = $controller($request);

        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $this->assertSame('public, s-maxage=2592000', $response->headers->get('Cache-Control'));
        $this->assertSame($this->getExpectedSitemapContent(['https://www.foobar.com/en/page1.html']), $response->getContent());
    }

    public function testSkipsNonHtmlPages(): void
    {
        $page1 = $this->mockPage([
            'id' => 43,
            'pid' => 42,
            'type' => 'custom1',
            'groups' => [],
            'published' => true,
            'rootLanguage' => 'en',
        ]);

        $page2 = $this->mockPage(
            [
                'id' => 44,
                'pid' => 43,
                'type' => 'custom2',
                'groups' => [],
                'published' => true,
                'rootLanguage' => 'en',
            ],
            ['' => 'https://www.foobar.com/en/page2.html'],
        );

        $pages = [
            42 => [$page1],
            43 => [$page2],
            44 => null,
            21 => null,
        ];

        $framework = $this->mockFrameworkWithPages($pages, [44 => null]);
        $container = $this->getContainer($framework);
        $request = Request::create('https://www.foobar.com/sitemap.xml');

        $registry = new PageRegistry($this->createMock(Connection::class));
        $registry->add('custom1', new RouteConfig(null, null, null, [], [], ['_format' => 'xml'], []));
        $registry->add('custom2', new RouteConfig());

        $controller = new SitemapController($registry, $this->mockPageFinder($request));
        $controller->setContainer($container);

        $response = $controller($request);

        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $this->assertSame('public, s-maxage=2592000', $response->headers->get('Cache-Control'));
        $this->assertSame($this->getExpectedSitemapContent(['https://www.foobar.com/en/page2.html']), $response->getContent());
    }

    private function getExpectedSitemapContent(array $urls): string
    {
        $result = '<?xml version="1.0" encoding="UTF-8"?>
<urlset xmlns="https://www.sitemaps.org/schemas/sitemap/0.9">';

        foreach ($urls as $url) {
            $result .= "
  <url>
    <loc>$url</loc>
  </url>";
        }

        return $result.'
</urlset>
';
    }

    /**
     * @return PageFinder&MockObject
     */
    private function mockPageFinder(Request $request): PageFinder
    {
        $rootPage1 = $this->mockClassWithProperties(PageModel::class);
        $rootPage1->id = 42;
        $rootPage1->dns = 'www.foobar.com';
        $rootPage1->urlPrefix = 'en';
        $rootPage1->language = 'en';
        $rootPage1->fallback = true;

        $rootPage2 = $this->mockClassWithProperties(PageModel::class);
        $rootPage2->id = 21;
        $rootPage2->dns = 'www.foobar.com';
        $rootPage2->urlPrefix = 'de';
        $rootPage2->language = 'de';
        $rootPage2->fallback = false;

        $pageFinder = $this->createMock(PageFinder::class);
        $pageFinder
            ->expects($this->once())
            ->method('findRootPagesForHost')
            ->with($request->getHost())
            ->willReturn([$rootPage1, $rootPage2])
        ;

        return $pageFinder;
    }

    /**
     * @return ContaoFramework&MockObject
     */
    private function mockFrameworkWithPages(array $pages, array $articles): ContaoFramework
    {
        $pageModelAdapter = $this->mockAdapter(['findPublishedRootPages', 'findByPid']);

        $pageModelAdapter
            ->expects($this->exactly(\count($pages)))
            ->method('findByPid')
            ->withConsecutive(...array_map(
                static fn ($parentId) => [$parentId, ['order' => 'sorting']],
                array_keys($pages),
            ))
            ->willReturnOnConsecutiveCalls(...$pages)
        ;

        $articleModelAdapter = $this->mockAdapter(['findPublishedWithTeaserByPid']);
        $articleModelAdapter
            ->expects($this->exactly(\count($articles)))
            ->method('findPublishedWithTeaserByPid')
            ->withConsecutive(...array_map(
                static fn ($pageId) => [$pageId, ['ignoreFePreview' => true]],
                array_keys($articles),
            ))
            ->willReturnOnConsecutiveCalls(...$articles)
        ;

        return $this->mockContaoFramework([
            PageModel::class => $pageModelAdapter,
            ArticleModel::class => $articleModelAdapter,
        ]);
    }

    /**
     * @param array<int> $allowedPageIds
     */
    private function getContainer(ContaoFramework $framework, array|null $allowedPageIds = null, string $baseUrl = 'https://www.foobar.com'): ContainerBuilder
    {
        $eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $eventDispatcher
            ->expects($this->once())
            ->method('dispatch')
            ->with($this->callback(
                function (SitemapEvent $event) use ($baseUrl) {
                    $this->assertSame($baseUrl.'/sitemap.xml', $event->getRequest()->getUri());
                    $this->assertSame([42, 21], $event->getRootPageIds());

                    return true;
                },
            ))
        ;

        $authorizationChecker = $this->createMock(AuthorizationCheckerInterface::class);

        if (null === $allowedPageIds) {
            $authorizationChecker
                ->method('isGranted')
                ->willReturn(true)
            ;
        } else {
            $authorizationChecker
                ->method('isGranted')
                ->willReturnCallback(
                    function (string $attribute, array $pageGroups) use ($allowedPageIds) {
                        $this->assertSame(ContaoCorePermissions::MEMBER_IN_GROUPS, $attribute);

                        return [] !== array_intersect(array_map('intval', $pageGroups), $allowedPageIds);
                    },
                )
            ;
        }

        $entityCacheTags = $this->createMock(EntityCacheTags::class);
        $entityCacheTags
            ->expects($this->once())
            ->method('tagWith')
            ->with([
                'contao.sitemap',
                'contao.sitemap.42',
                'contao.sitemap.21',
            ])
        ;

        $container = $this->getContainerWithContaoConfiguration();
        $container->set('contao.framework', $framework);
        $container->set('event_dispatcher', $eventDispatcher);
        $container->set('security.authorization_checker', $authorizationChecker);
        $container->set('contao.cache.entity_tags', $entityCacheTags);

        return $container;
    }

    private function mockPage(array $data, array|null $absoluteUrls = null): PageModel
    {
        $page = $this->mockClassWithProperties(PageModel::class, $data);
        $page
            ->expects($this->atLeastOnce())
            ->method('loadDetails')
        ;

        if (null !== $absoluteUrls) {
            $parameters = [];

            foreach (array_keys($absoluteUrls) as $suffix) {
                $parameters[] = [$suffix];
            }

            $page
                ->expects($this->exactly(\count($absoluteUrls)))
                ->method('getAbsoluteUrl')
                ->withConsecutive(...$parameters)
                ->willReturnOnConsecutiveCalls(...array_values($absoluteUrls))
            ;
        } else {
            $page
                ->expects($this->never())
                ->method('getAbsoluteUrl')
            ;
        }

        return $page;
    }
}
