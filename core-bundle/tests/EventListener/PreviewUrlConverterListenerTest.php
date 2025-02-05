<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\EventListener;

use Contao\ArticleModel;
use Contao\CoreBundle\Event\PreviewUrlConvertEvent;
use Contao\CoreBundle\EventListener\PreviewUrlConvertListener;
use Contao\CoreBundle\Exception\RouteParametersException;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Routing\ContentUrlGenerator;
use Contao\CoreBundle\Routing\Page\PageRegistry;
use Contao\CoreBundle\Routing\Page\PageRoute;
use Contao\CoreBundle\Tests\TestCase;
use Contao\PageModel;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\HttpFoundation\InputBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\UriSigner;
use Symfony\Component\Routing\Exception\MissingMandatoryParametersException;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class PreviewUrlConverterListenerTest extends TestCase
{
    public function testConvertsThePreviewUrlWithUrl(): void
    {
        $request = new Request();
        $request->query->set('url', 'en/content-elements.html');

        $event = new PreviewUrlConvertEvent($request);

        $listener = new PreviewUrlConvertListener(
            $this->mockContaoFramework(),
            $this->mockPageRegistry(),
            $this->createMock(ContentUrlGenerator::class),
            $this->createMock(UriSigner::class),
        );

        $listener($event);

        $this->assertSame('/en/content-elements.html', $event->getUrl());
    }

    public function testDoesNotConvertThePreviewUrlIfTheFrameworkIsNotInitialized(): void
    {
        $framework = $this->createMock(ContaoFramework::class);
        $framework
            ->method('isInitialized')
            ->willReturn(false)
        ;

        $event = new PreviewUrlConvertEvent(new Request());

        $listener = new PreviewUrlConvertListener(
            $framework,
            $this->mockPageRegistry(),
            $this->createMock(ContentUrlGenerator::class),
            $this->createMock(UriSigner::class),
        );

        $listener($event);

        $this->assertNull($event->getUrl());
    }

    public function testDoesNotConvertThePreviewUrlWithoutParameter(): void
    {
        $request = new Request();
        $event = new PreviewUrlConvertEvent($request);

        $listener = new PreviewUrlConvertListener(
            $this->mockContaoFramework(),
            $this->mockPageRegistry(),
            $this->createMock(ContentUrlGenerator::class),
            $this->createMock(UriSigner::class),
        );

        $listener($event);

        $this->assertNull($event->getUrl());
    }

    public function testConvertsThePreviewUrlWithPage(): void
    {
        $request = new Request();
        $request->query->set('page', '9');

        $pageModel = $this->mockClassWithProperties(PageModel::class);
        $pageModel->id = 9;

        $adapters = [
            PageModel::class => $this->mockConfiguredAdapter(['findWithDetails' => $pageModel]),
            ArticleModel::class => $this->mockConfiguredAdapter(['findByIdOrAliasAndPid' => null]),
        ];

        $urlGenerator = $this->createMock(ContentUrlGenerator::class);
        $urlGenerator
            ->expects($this->once())
            ->method('generate')
            ->with($pageModel, [], UrlGeneratorInterface::ABSOLUTE_URL)
            ->willReturn('https://example.com/en/content-elements.html')
        ;

        $event = new PreviewUrlConvertEvent($request);

        $listener = new PreviewUrlConvertListener(
            $this->mockContaoFramework($adapters),
            $this->mockPageRegistry(),
            $urlGenerator,
            $this->createMock(UriSigner::class),
        );

        $listener($event);

        $this->assertSame('https://example.com/en/content-elements.html', $event->getUrl());
    }

    public function testDoesNotConvertThePreviewUrlIfThereIsNoPage(): void
    {
        $request = new Request();
        $event = new PreviewUrlConvertEvent($request);

        $adapters = [
            PageModel::class => $this->mockConfiguredAdapter(['findWithDetails' => null]),
        ];

        $framework = $this->mockContaoFramework($adapters);

        $urlGenerator = $this->createMock(ContentUrlGenerator::class);
        $urlGenerator
            ->expects($this->never())
            ->method('generate')
        ;

        $listener = new PreviewUrlConvertListener(
            $framework,
            $this->mockPageRegistry(),
            $urlGenerator,
            $this->createMock(UriSigner::class),
        );

        $listener($event);

        $this->assertNull($event->getUrl());
    }

    public function testConvertsThePreviewUrlWithArticle(): void
    {
        $request = new Request();
        $request->query->set('page', '9');
        $request->query->set('article', 'foobar');

        $articleModel = $this->mockClassWithProperties(ArticleModel::class);
        $articleModel->alias = 'foobar';
        $articleModel->inColumn = 'main';

        $pageModel = $this->mockClassWithProperties(PageModel::class);
        $pageModel->id = 9;

        $adapters = [
            PageModel::class => $this->mockConfiguredAdapter(['findWithDetails' => $pageModel]),
            ArticleModel::class => $this->mockConfiguredAdapter(['findByIdOrAliasAndPid' => $articleModel]),
        ];

        $urlGenerator = $this->createMock(ContentUrlGenerator::class);
        $urlGenerator
            ->expects($this->once())
            ->method('generate')
            ->with($articleModel, [], UrlGeneratorInterface::ABSOLUTE_URL)
            ->willReturn('https://example.com/en/content-elements/articles/foobar.html')
        ;

        $event = new PreviewUrlConvertEvent($request);

        $listener = new PreviewUrlConvertListener(
            $this->mockContaoFramework($adapters),
            $this->mockPageRegistry(),
            $urlGenerator,
            $this->createMock(UriSigner::class),
        );

        $listener($event);

        $this->assertSame('https://example.com/en/content-elements/articles/foobar.html', $event->getUrl());
    }

    public function testRediectsToFragmentUrlIfPreviewUrlThrowsException(): void
    {
        $pageModel = $this->mockClassWithProperties(PageModel::class, [
            'id' => 42,
            'rootLanguage' => 'en',
            'urlPrefix' => '',
            'urlSuffix' => '',
        ]);

        $route = new PageRoute($pageModel);

        $request = $this->createMock(Request::class);
        $request->query = new InputBag(['page' => '9']);

        $request
            ->expects($this->once())
            ->method('getUriForPath')
            ->willReturnArgument(0)
        ;

        $adapters = [
            PageModel::class => $this->mockConfiguredAdapter(['findWithDetails' => $pageModel]),
        ];

        $urlGenerator = $this->createMock(ContentUrlGenerator::class);
        $urlGenerator
            ->expects($this->once())
            ->method('generate')
            ->with($pageModel, [], UrlGeneratorInterface::ABSOLUTE_URL)
            ->willThrowException(new RouteNotFoundException())
        ;

        $uriSigner = $this->createMock(UriSigner::class);
        $uriSigner
            ->expects($this->once())
            ->method('sign')
            ->willReturnArgument(0)
        ;

        $framework = $this->mockContaoFramework($adapters);
        $event = new PreviewUrlConvertEvent($request);

        $listener = new PreviewUrlConvertListener(
            $framework,
            $this->mockPageRegistry($route),
            $urlGenerator,
            $uriSigner,
        );

        $listener($event);

        $defaults = $route->getDefaults();
        $defaults['pageModel'] = 42;

        $expectedUrl = '/_fragment?_path='.urlencode(http_build_query($defaults));

        $this->assertNull($event->getResponse());
        $this->assertSame($expectedUrl, $event->getUrl());
    }

    public function testReturnsEmptyUrlForPagesThatRequireAnItem(): void
    {
        $pageModel = $this->mockClassWithProperties(PageModel::class, [
            'id' => 42,
            'rootLanguage' => 'en',
            'urlPrefix' => '',
            'urlSuffix' => '',
            'requireItem' => '1',
        ]);

        $route = new PageRoute($pageModel);

        $request = $this->createMock(Request::class);
        $request->query = new InputBag(['page' => '42']);

        $adapters = [
            PageModel::class => $this->mockConfiguredAdapter(['findWithDetails' => $pageModel]),
        ];

        $urlGenerator = $this->createMock(ContentUrlGenerator::class);
        $urlGenerator
            ->expects($this->once())
            ->method('generate')
            ->with($pageModel, [], UrlGeneratorInterface::ABSOLUTE_URL)
            ->willThrowException(
                new RouteParametersException(
                    $route,
                    [],
                    UrlGeneratorInterface::ABSOLUTE_URL,
                    new MissingMandatoryParametersException('tl_page.42', ['requireItem']),
                ),
            )
        ;

        $uriSigner = $this->createMock(UriSigner::class);
        $uriSigner
            ->expects($this->never())
            ->method('sign')
        ;

        $framework = $this->mockContaoFramework($adapters);
        $event = new PreviewUrlConvertEvent($request);

        $listener = new PreviewUrlConvertListener(
            $framework,
            $this->mockPageRegistry(),
            $urlGenerator,
            $uriSigner,
        );

        $listener($event);

        $this->assertNull($event->getResponse());
        $this->assertNull($event->getUrl());
    }

    private function mockPageRegistry(PageRoute|null $route = null): PageRegistry&MockObject
    {
        $pageRegistry = $this->createMock(PageRegistry::class);

        if (!$route) {
            $pageRegistry
                ->expects($this->never())
                ->method('getRoute')
            ;
        } else {
            $pageRegistry
                ->expects($this->once())
                ->method('getRoute')
                ->willReturn($route)
            ;
        }

        return $pageRegistry;
    }
}
