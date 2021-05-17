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

use Contao\Backend;
use Contao\CoreBundle\Controller\SitemapController;
use Contao\CoreBundle\Event\SitemapEvent;
use Contao\CoreBundle\Tests\TestCase;
use Contao\FrontendUser;
use Contao\PageModel;
use Contao\System;
use FOS\HttpCache\ResponseTagger;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class SitemapControllerTest extends TestCase
{
    public function testNoSitemapIfNoRootPageFound(): void
    {
        $pageModelAdapter = $this->mockAdapter(['findPublishedRootPages']);
        $pageModelAdapter
            ->expects($this->exactly(2))
            ->method('findPublishedRootPages')
            ->withConsecutive(
                [['dns' => 'www.foobar.com']],
                [['dns' => '']]
            )
            ->willReturn(null)
        ;

        $framework = $this->mockContaoFramework([PageModel::class => $pageModelAdapter]);
        $framework
            ->expects($this->once())
            ->method('initialize')
        ;

        $eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $eventDispatcher
            ->expects($this->never())
            ->method('dispatch')
        ;

        $container = $this->getContainerWithContaoConfiguration();
        $container->set('contao.framework', $framework);
        $container->set('event_dispatcher', $eventDispatcher);

        $request = Request::create('https://www.foobar.com/sitemap.xml');
        $controller = new SitemapController();
        $controller->setContainer($container);
        $response = $controller($request);

        $this->assertSame(Response::HTTP_NOT_FOUND, $response->getStatusCode());
    }

    public function testSitemapWithNoUserLoggedIn(): void
    {
        $tokenStorage = $this->createMock(TokenStorageInterface::class);
        $tokenStorage
            ->method('getToken')
            ->willReturn(null)
        ;

        $response = $this->getResponseForValidCall($tokenStorage);

        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $this->assertSame('public, s-maxage=2592000', $response->headers->get('Cache-Control'));
        $this->assertSame($this->getExpectedSitemapContent(), $response->getContent());
    }

    public function testSitemapWithUserLoggedIn(): void
    {
        $response = $this->getResponseForValidCall($this->mockTokenStorage(FrontendUser::class));

        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $this->assertSame('no-store, private', $response->headers->get('Cache-Control'));
        $this->assertSame($this->getExpectedSitemapContent(), $response->getContent());
    }

    private function getResponseForValidCall($tokenStorage): Response
    {
        /** @var PageModel $rootPage1 */
        $rootPage1 = $this->mockClassWithProperties(PageModel::class);
        $rootPage1->id = 42;
        $rootPage1->dns = 'www.foobar.com';
        $rootPage1->urlPrefix = 'en';
        $rootPage1->fallback = '1';

        /** @var PageModel $rootPage2 */
        $rootPage2 = $this->mockClassWithProperties(PageModel::class);
        $rootPage2->id = 21;
        $rootPage2->dns = 'www.foobar.com';
        $rootPage2->urlPrefix = 'de';
        $rootPage2->fallback = '';

        $pageModelAdapter = $this->mockAdapter(['findPublishedRootPages']);
        $pageModelAdapter
            ->expects($this->once())
            ->method('findPublishedRootPages')
            ->with(['dns' => 'www.foobar.com'])
            ->willReturn([$rootPage1, $rootPage2])
        ;

        $backendAdapter = $this->mockAdapter(['findSearchablePages']);
        $backendAdapter
            ->expects($this->exactly(2))
            ->method('findSearchablePages')
            ->withConsecutive(
                [42, '', true],
                [21, '', true]
            )
            ->willReturnOnConsecutiveCalls(
                ['https://www.foobar.com/en/page1.html', 'https://www.foobar.com/en/page2.html'],
                ['https://www.foobar.com/de/page1.html']
            )
        ;

        $systemAdapter = $this->mockAdapter(['importStatic']);
        $systemAdapter
            ->expects($this->never())
            ->method('importStatic')
        ;

        $framework = $this->mockContaoFramework([
            PageModel::class => $pageModelAdapter,
            Backend::class => $backendAdapter,
            System::class => $systemAdapter,
        ]);

        $framework
            ->expects($this->once())
            ->method('initialize')
        ;

        $eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $eventDispatcher
            ->expects($this->once())
            ->method('dispatch')
            ->with($this->callback(
                function (SitemapEvent $event) {
                    $this->assertSame('https://www.foobar.com/sitemap.xml', $event->getRequest()->getUri());
                    $this->assertSame([42, 21], $event->getRootPageIds());

                    return true;
                }
            ))
        ;

        $responseTagger = $this->createMock(ResponseTagger::class);
        $responseTagger
            ->expects($this->once())
            ->method('addTags')
            ->with([
                'contao.sitemap',
                'contao.sitemap.42',
                'contao.sitemap.21',
            ])
        ;

        $container = $this->getContainerWithContaoConfiguration();
        $container->set('contao.framework', $framework);
        $container->set('event_dispatcher', $eventDispatcher);
        $container->set('security.token_storage', $tokenStorage);
        $container->set('fos_http_cache.http.symfony_response_tagger', $responseTagger);

        $request = Request::create('https://www.foobar.com/sitemap.xml');
        $controller = new SitemapController();
        $controller->setContainer($container);

        return $controller($request);
    }

    private function getExpectedSitemapContent(): string
    {
        return <<<'SITEMAP'
            <?xml version="1.0" encoding="UTF-8"?>
            <urlset xmlns="https://www.sitemaps.org/schemas/sitemap/0.9">
              <url>
                <loc>https://www.foobar.com/en/page1.html</loc>
              </url>
              <url>
                <loc>https://www.foobar.com/en/page2.html</loc>
              </url>
              <url>
                <loc>https://www.foobar.com/de/page1.html</loc>
              </url>
            </urlset>

            SITEMAP;
    }
}
