<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Controller\Page;

use Contao\CoreBundle\Controller\Page\RootPageController;
use Contao\CoreBundle\Exception\NoActivePageFoundException;
use Contao\CoreBundle\Routing\Page\PageRegistry;
use Contao\CoreBundle\Routing\Page\PageRoute;
use Contao\CoreBundle\Tests\TestCase;
use Contao\PageModel;
use Contao\System;
use Psr\Log\NullLogger;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;

class RootPageControllerTest extends TestCase
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

    public function testRedirectsToTheFirstChildPage(): void
    {
        $rootPage = $this->mockClassWithProperties(PageModel::class, ['id' => 42]);

        $childPage = $this->mockClassWithProperties(PageModel::class);
        $childPage
            ->expects($this->once())
            ->method('getAbsoluteUrl')
            ->willReturn('https://example.com/foobar')
        ;

        $adapter = $this->mockAdapter(['findFirstPublishedByPid']);
        $adapter
            ->expects($this->once())
            ->method('findFirstPublishedByPid')
            ->with(42)
            ->willReturn($childPage)
        ;

        $container = $this->getContainerWithContaoConfiguration();
        $container->set('contao.framework', $this->mockContaoFramework([PageModel::class => $adapter]));

        $controller = new RootPageController($this->createMock(PageRegistry::class), new NullLogger());
        $controller->setContainer($container);

        /** @var RedirectResponse $response */
        $response = $controller($rootPage);

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame('https://example.com/foobar', $response->getTargetUrl());
        $this->assertSame(Response::HTTP_FOUND, $response->getStatusCode());
    }

    public function testThrowsExceptionIfNoRedirectPageIsFound(): void
    {
        $rootPage = $this->mockClassWithProperties(PageModel::class, ['id' => 42]);

        $adapter = $this->mockAdapter(['findFirstPublishedByPid']);
        $adapter
            ->expects($this->once())
            ->method('findFirstPublishedByPid')
            ->with(42)
            ->willReturn(null)
        ;

        $container = $this->getContainerWithContaoConfiguration();
        $container->set('contao.framework', $this->mockContaoFramework([PageModel::class => $adapter]));

        $controller = new RootPageController($this->createMock(PageRegistry::class), new NullLogger());
        $controller->setContainer($container);

        $this->expectException(NoActivePageFoundException::class);
        $this->expectExceptionMessage('No active page found under root page.');

        $controller($rootPage);
    }

    public function testReturnsPageRouteForFirstSubpage(): void
    {
        $rootPage = $this->mockClassWithProperties(PageModel::class, ['id' => 42]);
        $childPage = $this->mockClassWithProperties(PageModel::class);
        $rootRoute = new PageRoute($rootPage);
        $childRoute = new PageRoute($childPage);

        $adapter = $this->mockAdapter(['findFirstPublishedByPid']);
        $adapter
            ->expects($this->once())
            ->method('findFirstPublishedByPid')
            ->with(42)
            ->willReturn($childPage)
        ;

        $pageRegistry = $this->createMock(PageRegistry::class);
        $pageRegistry
            ->expects($this->once())
            ->method('getRoute')
            ->with($childPage)
            ->willReturn($childRoute)
        ;

        $container = $this->getContainerWithContaoConfiguration();
        $container->set('contao.framework', $this->mockContaoFramework([PageModel::class => $adapter]));

        $controller = new RootPageController($pageRegistry, new NullLogger());
        $controller->setContainer($container);

        $result = $controller->configurePageRoute($rootRoute);

        $this->assertSame($childRoute, $result);
    }
}
