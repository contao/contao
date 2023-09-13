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
use Contao\CoreBundle\Routing\Page\PageRegistry;
use Contao\CoreBundle\Routing\Page\PageRoute;
use Contao\CoreBundle\Tests\TestCase;
use Contao\PageModel;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\UriSigner;
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

        $pageModel
            ->expects($this->once())
            ->method('getPreviewUrl')
            ->with(null)
            ->willReturn('/en/content-elements.html')
        ;

        $adapters = [
            PageModel::class => $this->mockConfiguredAdapter(['findWithDetails' => $pageModel]),
            ArticleModel::class => $this->mockConfiguredAdapter(['findByIdOrAliasAndPid' => null]),
        ];

        $framework = $this->mockContaoFramework($adapters);
        $event = new PreviewUrlConvertEvent($request);

        $listener = new PreviewUrlConvertListener(
            $framework,
            $this->mockPageRegistry(),
            $this->createMock(UriSigner::class),
        );

        $listener($event);

        $this->assertSame('/en/content-elements.html', $event->getUrl());
    }

    public function testDoesNotConvertThePreviewUrlIfThereIsNoPage(): void
    {
        $request = new Request();
        $event = new PreviewUrlConvertEvent($request);

        $adapters = [
            PageModel::class => $this->mockConfiguredAdapter(['findWithDetails' => null]),
        ];

        $framework = $this->mockContaoFramework($adapters);

        $listener = new PreviewUrlConvertListener(
            $framework,
            $this->mockPageRegistry(),
            $this->createMock(UriSigner::class),
        );

        $listener($event);

        $this->assertNull($event->getUrl());
    }

    public function testConvertsThePreviewUrlWithPageAndArticle(): void
    {
        $request = new Request();
        $request->query->set('page', '9');
        $request->query->set('article', 'foobar');

        $articleModel = $this->mockClassWithProperties(ArticleModel::class);
        $articleModel->alias = 'foobar';
        $articleModel->inColumn = 'main';

        $pageModel = $this->mockClassWithProperties(PageModel::class);
        $pageModel->id = 9;

        $pageModel
            ->expects($this->once())
            ->method('getPreviewUrl')
            ->with('/articles/foobar')
            ->willReturn('/en/content-elements/articles/foobar.html')
        ;

        $adapters = [
            PageModel::class => $this->mockConfiguredAdapter(['findWithDetails' => $pageModel]),
            ArticleModel::class => $this->mockConfiguredAdapter(['findByIdOrAliasAndPid' => $articleModel]),
        ];

        $framework = $this->mockContaoFramework($adapters);
        $event = new PreviewUrlConvertEvent($request);

        $listener = new PreviewUrlConvertListener(
            $framework,
            $this->mockPageRegistry(),
            $this->createMock(UriSigner::class),
        );

        $listener($event);
    }

    public function testRediectsToFragmentUrlIfPreviewUrlThrowsException(): void
    {
        $pageModel = $this->mockClassWithProperties(PageModel::class, ['id' => 42, 'rootLanguage' => 'en']);
        $pageModel
            ->expects($this->once())
            ->method('getPreviewUrl')
            ->willThrowException(new RouteNotFoundException())
        ;

        $route = new PageRoute($pageModel);

        $request = $this->createMock(Request::class);
        $request->query = new ParameterBag(['page' => '9']);
        $request
            ->expects($this->once())
            ->method('getUriForPath')
            ->willReturnArgument(0)
        ;

        $adapters = [
            PageModel::class => $this->mockConfiguredAdapter(['findWithDetails' => $pageModel]),
        ];

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
            'requireItem' => '1',
        ]);

        $route = new PageRoute($pageModel);

        $pageModel
            ->expects($this->once())
            ->method('getPreviewUrl')
            ->willThrowException(
                new RouteParametersException(
                    $route,
                    [],
                    UrlGeneratorInterface::ABSOLUTE_URL,
                    new MissingMandatoryParametersException('tl_page.42', ['requireItem']),
                ),
            )
        ;

        $request = $this->createMock(Request::class);
        $request->query = new ParameterBag(['page' => '42']);

        $adapters = [
            PageModel::class => $this->mockConfiguredAdapter(['findWithDetails' => $pageModel]),
        ];

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
