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
use Contao\CoreBundle\Routing\Page\PageRoute;
use Contao\CoreBundle\Routing\RedirectRoute;
use Contao\CoreBundle\Tests\TestCase;
use Contao\PageModel;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Container\ContainerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class ForwardPageControllerTest extends TestCase
{
    /**
     * @var PageModel&MockObject
     */
    private $pageModelAdapter;

    /**
     * @var UrlGeneratorInterface&MockObject
     */
    private $router;

    /**
     * @var ForwardPageController
     */
    private $controller;

    protected function setUp(): void
    {
        /** @var PageModel&MockObject $pageModelAdapter */
        $pageModelAdapter = $this->mockAdapter(['findPublishedById', 'findFirstPublishedRegularByPid']);
        $this->pageModelAdapter = $pageModelAdapter;

        $this->router = $this->createMock(UrlGeneratorInterface::class);

        $framework = $this->mockContaoFramework([PageModel::class => $this->pageModelAdapter]);

        $container = $this->createMock(ContainerInterface::class);
        $container
            ->method('get')
            ->willReturnMap(
                [
                    ['router', $this->router],
                    ['contao.framework', $framework],
                ]
            )
        ;

        $this->controller = new ForwardPageController();
        $this->controller->setContainer($container);
    }

    public function testSetsThePageRouteTargetUrlToJumpTo(): void
    {
        /** @var PageModel&MockObject $page */
        $page = $this->mockClassWithProperties(PageModel::class, ['jumpTo' => 17]);
        $route = new PageRoute($page);

        /** @var PageModel&MockObject $nextPage */
        $nextPage = $this->mockClassWithProperties(PageModel::class, ['id' => 17]);

        $this->pageModelAdapter
            ->expects($this->once())
            ->method('findPublishedById')
            ->with(17)
            ->willReturn($nextPage)
        ;

        $this->router
            ->expects($this->once())
            ->method('generate')
            ->with(PageRoute::ROUTE_NAME, [PageRoute::CONTENT_PARAMETER => $nextPage])
            ->willReturn('https://www.example.org/en/foobar.html')
        ;

        $targetRoute = $this->controller->enhancePageRoute($route);

        $this->assertSame($route, $targetRoute);
        $this->assertTrue($targetRoute->hasOption(RedirectRoute::TARGET_URL));
        $this->assertSame('https://www.example.org/en/foobar.html', $targetRoute->getOption(RedirectRoute::TARGET_URL));
    }

    public function testSetsThePageRouteTargetUrlToFirstSubpageIfJumpToIsEmpty(): void
    {
        /** @var PageModel&MockObject $page */
        $page = $this->mockClassWithProperties(PageModel::class, ['id' => 1, 'jumpTo' => null]);
        $route = new PageRoute($page);

        /** @var PageModel&MockObject $nextPage */
        $nextPage = $this->mockClassWithProperties(PageModel::class, ['id' => 2]);

        $this->pageModelAdapter
            ->expects($this->once())
            ->method('findFirstPublishedRegularByPid')
            ->with(1)
            ->willReturn($nextPage)
        ;

        $this->router
            ->expects($this->once())
            ->method('generate')
            ->with(PageRoute::ROUTE_NAME, [PageRoute::CONTENT_PARAMETER => $nextPage])
            ->willReturn('https://www.example.org/en/foobar.html')
        ;

        $targetRoute = $this->controller->enhancePageRoute($route);

        $this->assertSame($route, $targetRoute);
        $this->assertTrue($targetRoute->hasOption(RedirectRoute::TARGET_URL));
        $this->assertSame('https://www.example.org/en/foobar.html', $targetRoute->getOption(RedirectRoute::TARGET_URL));
    }

    public function testThrowsExceptionIfForwardPageIsNotFound(): void
    {
        /** @var PageModel&MockObject $page */
        $page = $this->mockClassWithProperties(PageModel::class, ['jumpTo' => 17]);
        $route = new PageRoute($page);

        $this->pageModelAdapter
            ->expects($this->once())
            ->method('findPublishedById')
            ->with(17)
            ->willReturn(null)
        ;

        $this->expectException(ForwardPageNotFoundException::class);

        $this->controller->enhancePageRoute($route);
    }

    public function testDoesNotAddUrlSuffixes(): void
    {
        $this->assertSame([], $this->controller->getUrlSuffixes());
    }
}
