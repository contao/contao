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

use Contao\CoreBundle\Controller\Page\ForwardPageController;
use Contao\CoreBundle\Exception\ForwardPageNotFoundException;
use Contao\CoreBundle\Routing\ContentUrlGenerator;
use Contao\CoreBundle\Routing\Page\PageRoute;
use Contao\CoreBundle\Tests\TestCase;
use Contao\PageModel;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class ForwardPageControllerTest extends TestCase
{
    public function testForwardsToJumpToPage(): void
    {
        $pageModel = $this->createClassWithPropertiesStub(PageModel::class, ['jumpTo' => 42]);
        $forwardPageModel = $this->createClassWithPropertiesStub(PageModel::class);

        $pageAdapter = $this->createAdapterMock(['findPublishedById']);
        $pageAdapter
            ->expects($this->once())
            ->method('findPublishedById')
            ->with(42)
            ->willReturn($forwardPageModel)
        ;

        $framework = $this->createContaoFrameworkStub([
            PageModel::class => $pageAdapter,
        ]);

        $contentUrlGenerator = $this->createMock(ContentUrlGenerator::class);
        $contentUrlGenerator
            ->expects($this->once())
            ->method('generate')
            ->with($forwardPageModel, [], UrlGeneratorInterface::ABSOLUTE_URL)
            ->willReturn('https://example.org/')
        ;

        $container = $this->getContainerWithContaoConfiguration();
        $container->set('contao.framework', $framework);
        $container->set('contao.routing.content_url_generator', $contentUrlGenerator);

        $controller = new ForwardPageController();
        $controller->setContainer($container);

        $response = $controller(new Request(), $pageModel);

        $this->assertSame('https://example.org/', $response->getTargetUrl());
    }

    public function testThrowsExceptionIfJumpToPageDoesNotExist(): void
    {
        $pageModel = $this->createClassWithPropertiesStub(PageModel::class, ['jumpTo' => 42]);

        $pageAdapter = $this->createAdapterMock(['findPublishedById']);
        $pageAdapter
            ->expects($this->once())
            ->method('findPublishedById')
            ->with(42)
            ->willReturn(null)
        ;

        $framework = $this->createContaoFrameworkStub([
            PageModel::class => $pageAdapter,
        ]);

        $container = $this->getContainerWithContaoConfiguration();
        $container->set('contao.framework', $framework);

        $controller = new ForwardPageController();
        $controller->setContainer($container);

        $this->expectException(ForwardPageNotFoundException::class);

        $controller(new Request(), $pageModel);
    }

    public function testForwardsToFirstRegularSubpage(): void
    {
        $pageModel = $this->createClassWithPropertiesStub(PageModel::class, ['id' => 42, 'jumpTo' => 0]);
        $forwardPageModel = $this->createClassWithPropertiesStub(PageModel::class);

        $pageAdapter = $this->createAdapterMock(['findFirstPublishedRegularByPid']);
        $pageAdapter
            ->expects($this->once())
            ->method('findFirstPublishedRegularByPid')
            ->with(42)
            ->willReturn($forwardPageModel)
        ;

        $framework = $this->createContaoFrameworkStub([
            PageModel::class => $pageAdapter,
        ]);

        $contentUrlGenerator = $this->createMock(ContentUrlGenerator::class);
        $contentUrlGenerator
            ->expects($this->once())
            ->method('generate')
            ->with($forwardPageModel, [], UrlGeneratorInterface::ABSOLUTE_URL)
            ->willReturn('https://example.org/')
        ;

        $container = $this->getContainerWithContaoConfiguration();
        $container->set('contao.framework', $framework);
        $container->set('contao.routing.content_url_generator', $contentUrlGenerator);

        $controller = new ForwardPageController();
        $controller->setContainer($container);

        $response = $controller(new Request(), $pageModel);

        $this->assertSame('https://example.org/', $response->getTargetUrl());
    }

    public function testThrowsExceptionIfFirstRegularSubpageDoesNotExist(): void
    {
        $pageModel = $this->createClassWithPropertiesStub(PageModel::class, ['id' => 42]);

        $pageAdapter = $this->createAdapterMock(['findFirstPublishedRegularByPid']);
        $pageAdapter
            ->expects($this->once())
            ->method('findFirstPublishedRegularByPid')
            ->with(42)
            ->willReturn(null)
        ;

        $framework = $this->createContaoFrameworkStub([
            PageModel::class => $pageAdapter,
        ]);

        $container = $this->getContainerWithContaoConfiguration();
        $container->set('contao.framework', $framework);

        $controller = new ForwardPageController();
        $controller->setContainer($container);

        $this->expectException(ForwardPageNotFoundException::class);

        $controller(new Request(), $pageModel);
    }

    public function testForwardsTheQueryString(): void
    {
        $pageModel = $this->createClassWithPropertiesStub(PageModel::class, ['jumpTo' => 42]);
        $forwardPageModel = $this->createClassWithPropertiesStub(PageModel::class);

        $pageAdapter = $this->createAdapterMock(['findPublishedById']);
        $pageAdapter
            ->expects($this->once())
            ->method('findPublishedById')
            ->with(42)
            ->willReturn($forwardPageModel)
        ;

        $framework = $this->createContaoFrameworkStub([
            PageModel::class => $pageAdapter,
        ]);

        $contentUrlGenerator = $this->createMock(ContentUrlGenerator::class);
        $contentUrlGenerator
            ->expects($this->once())
            ->method('generate')
            ->with($forwardPageModel, [], UrlGeneratorInterface::ABSOLUTE_URL)
            ->willReturn('https://example.org/')
        ;

        $container = $this->getContainerWithContaoConfiguration();
        $container->set('contao.framework', $framework);
        $container->set('contao.routing.content_url_generator', $contentUrlGenerator);

        $controller = new ForwardPageController();
        $controller->setContainer($container);

        $response = $controller(new Request(['foo' => 'bar', 'bar' => 'baz']), $pageModel);

        $this->assertSame('https://example.org/?foo=bar&bar=baz', $response->getTargetUrl());
    }

    public function testDoesNotChangeTheRouteIfAlwaysForwardIsEnabled(): void
    {
        $pageModel = $this->createClassWithPropertiesStub(PageModel::class, ['alwaysForward' => true]);

        $route = $this->createMock(PageRoute::class);
        $route
            ->method('getPageModel')
            ->willReturn($pageModel)
        ;

        $route
            ->expects($this->never())
            ->method('setPath')
        ;

        $route
            ->expects($this->never())
            ->method('setRequirements')
        ;

        $route
            ->expects($this->never())
            ->method('setDefaults')
        ;

        $controller = new ForwardPageController();
        $controller->configurePageRoute($route);
    }

    public function testChangesTheRouteIfAlwaysForwardIsDisabled(): void
    {
        $pageModel = $this->createClassWithPropertiesStub(PageModel::class, ['alias' => 'foobar', 'alwaysForward' => false]);

        $route = $this->createMock(PageRoute::class);
        $route
            ->method('getPageModel')
            ->willReturn($pageModel)
        ;

        $route
            ->method('getRequirements')
            ->willReturn(['parameters' => '(/.+?)?'])
        ;

        $route
            ->method('getDefaults')
            ->willReturn(['parameters' => ''])
        ;

        $route
            ->expects($this->once())
            ->method('setPath')
            ->with('/foobar')
        ;

        $route
            ->expects($this->once())
            ->method('setRequirements')
            ->with([])
        ;

        $route
            ->expects($this->once())
            ->method('setDefaults')
            ->with([])
        ;

        $controller = new ForwardPageController();
        $controller->configurePageRoute($route);
    }
}
