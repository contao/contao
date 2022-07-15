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
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Routing\Page\PageRegistry;
use Contao\CoreBundle\Routing\Page\PageRoute;
use Contao\CoreBundle\Tests\TestCase;
use Contao\PageModel;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Routing\Exception\RouteNotFoundException;

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
            $this->createMock(HttpKernelInterface::class)
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
            $this->createMock(HttpKernelInterface::class)
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
            $this->createMock(HttpKernelInterface::class)
        );

        $listener($event);

        $this->assertNull($event->getUrl());
    }

    public function testConvertsThePreviewUrlWithPage(): void
    {
        $request = new Request();
        $request->query->set('page', '9');

        $pageModel = $this->createMock(PageModel::class);
        $pageModel
            ->expects($this->once())
            ->method('getPreviewUrl')
            ->with(null)
            ->willReturn('/en/content-elements.html')
        ;

        $adapters = [
            PageModel::class => $this->mockConfiguredAdapter(['findWithDetails' => $pageModel]),
            ArticleModel::class => $this->mockConfiguredAdapter(['findByAlias' => null]),
        ];

        $framework = $this->mockContaoFramework($adapters);
        $event = new PreviewUrlConvertEvent($request);

        $listener = new PreviewUrlConvertListener(
            $framework,
            $this->mockPageRegistry(),
            $this->createMock(HttpKernelInterface::class)
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
            $this->createMock(HttpKernelInterface::class)
        );

        $listener($event);

        $this->assertNull($event->getUrl());
    }

    public function testConvertsThePreviewUrlWithPageAndArticle(): void
    {
        $request = new Request();
        $request->query->set('page', '9');
        $request->query->set('article', 'foobar');

        /** @var ArticleModel $articleModel */
        $articleModel = $this->mockClassWithProperties(ArticleModel::class);
        $articleModel->alias = 'foobar';
        $articleModel->inColumn = 'main';

        $pageModel = $this->createMock(PageModel::class);
        $pageModel
            ->expects($this->once())
            ->method('getPreviewUrl')
            ->with('/articles/foobar')
            ->willReturn('/en/content-elements/articles/foobar.html')
        ;

        $adapters = [
            PageModel::class => $this->mockConfiguredAdapter(['findWithDetails' => $pageModel]),
            ArticleModel::class => $this->mockConfiguredAdapter(['findByAlias' => $articleModel]),
        ];

        $framework = $this->mockContaoFramework($adapters);
        $event = new PreviewUrlConvertEvent($request);

        $listener = new PreviewUrlConvertListener(
            $framework,
            $this->mockPageRegistry(),
            $this->createMock(HttpKernelInterface::class)
        );

        $listener($event);
    }

    public function testForwardsToControllerIfPreviewUrlThrowsException(): void
    {
        $pageModel = $this->mockClassWithProperties(PageModel::class, ['rootLanguage' => 'en']);
        $pageModel
            ->expects($this->once())
            ->method('getPreviewUrl')
            ->willThrowException(new RouteNotFoundException())
        ;

        $subRequest = $this->createMock(Request::class);
        $route = new PageRoute($pageModel);

        $request = $this->createMock(Request::class);
        $request->query = new ParameterBag(['page' => '9']);
        $request
            ->expects($this->once())
            ->method('duplicate')
            ->with(null, null, $route->getDefaults())
            ->willReturn($subRequest)
        ;

        $response = new Response();

        $adapters = [
            PageModel::class => $this->mockConfiguredAdapter(['findWithDetails' => $pageModel]),
        ];

        $httpKernel = $this->createMock(HttpKernelInterface::class);
        $httpKernel
            ->expects($this->once())
            ->method('handle')
            ->with($subRequest, HttpKernelInterface::SUB_REQUEST)
            ->willReturn($response)
        ;

        $framework = $this->mockContaoFramework($adapters);
        $event = new PreviewUrlConvertEvent($request);

        $listener = new PreviewUrlConvertListener(
            $framework,
            $this->mockPageRegistry($route),
            $httpKernel
        );

        $listener($event);

        $this->assertSame($response, $event->getResponse());
    }

    /**
     * @return PageRegistry&MockObject
     */
    private function mockPageRegistry(PageRoute $route = null): PageRegistry
    {
        $pageRegistry = $this->createMock(PageRegistry::class);

        if (null === $route) {
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
