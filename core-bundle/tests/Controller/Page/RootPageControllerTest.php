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
use Contao\CoreBundle\Routing\Page\PageRoute;
use Contao\CoreBundle\Tests\TestCase;
use Contao\PageModel;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Container\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class RootPageControllerTest extends TestCase
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
     * @var RootPageController
     */
    private $controller;

    protected function setUp(): void
    {
        /** @var PageModel&MockObject $pageModelAdapter */
        $pageModelAdapter = $this->mockAdapter(['findFirstPublishedByPid']);
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

        $this->controller = new RootPageController();
        $this->controller->setContainer($container);
    }

    public function testThrowsExceptionIfPageTypeIsNotSupported(): void
    {
        /** @var PageModel&MockObject $page */
        $page = $this->mockClassWithProperties(PageModel::class, ['type' => 'foobar']);

        $this->expectException(\InvalidArgumentException::class);

        $this->controller->__invoke($page);
    }

    public function testThrowsExceptionIfFirstPageOfRootIsNotFound(): void
    {
        /** @var PageModel&MockObject $page */
        $page = $this->mockClassWithProperties(PageModel::class, ['id' => 17, 'type' => 'root']);

        $this->pageModelAdapter
            ->expects($this->once())
            ->method('findFirstPublishedByPid')
            ->with(17)
            ->willReturn(null)
        ;

        $this->expectException(NoActivePageFoundException::class);

        $this->controller->__invoke($page);
    }

    public function testCreatesRedirectResponseToFirstPage(): void
    {
        /** @var PageModel&MockObject $page */
        $page = $this->mockClassWithProperties(PageModel::class, ['id' => 17, 'type' => 'root', 'alias' => 'root', 'urlPrefix' => 'en', 'urlSuffix' => '.html']);

        /** @var PageModel&MockObject $nextPage */
        $nextPage = $this->mockClassWithProperties(PageModel::class, ['id' => 18, 'pid' => 17, 'type' => 'root']);

        $this->pageModelAdapter
            ->expects($this->once())
            ->method('findFirstPublishedByPid')
            ->with(17)
            ->willReturn($nextPage)
        ;

        $this->router
            ->expects($this->once())
            ->method('generate')
            ->with(PageRoute::ROUTE_NAME, [PageRoute::CONTENT_PARAMETER => $nextPage])
            ->willReturn('https://www.example.org/en/foobar.html')
        ;

        /** @var RedirectResponse $response */
        $response = $this->controller->__invoke($page);

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame('https://www.example.org/en/foobar.html', $response->getTargetUrl());
    }
}
