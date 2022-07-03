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

use Contao\CoreBundle\Controller\RobotsTxtController;
use Contao\CoreBundle\Tests\TestCase;
use Contao\PageModel;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class RobotsTxtControllerTest extends TestCase
{
    public function testRobotsTxtIfNoRootPageFound(): void
    {
        $pageModelAdapter = $this->mockAdapter(['findPublishedFallbackByHostname']);
        $pageModelAdapter
            ->expects($this->once())
            ->method('findPublishedFallbackByHostname')
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

        $request = Request::create('/robots.txt');
        $controller = new RobotsTxtController($framework, $eventDispatcher);
        $response = $controller($request);

        $this->assertSame(Response::HTTP_NOT_FOUND, $response->getStatusCode());
    }

    public function testRobotsTxt(): void
    {
        $pageModel = $this->mockClassWithProperties(PageModel::class);

        $pageModelAdapter = $this->mockAdapter(['findPublishedFallbackByHostname']);
        $pageModelAdapter
            ->expects($this->once())
            ->method('findPublishedFallbackByHostname')
            ->willReturn($pageModel)
        ;

        $framework = $this->mockContaoFramework([PageModel::class => $pageModelAdapter]);
        $framework
            ->expects($this->once())
            ->method('initialize')
        ;

        $eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $eventDispatcher
            ->expects($this->once())
            ->method('dispatch')
        ;

        $request = Request::create('/robots.txt');
        $controller = new RobotsTxtController($framework, $eventDispatcher);
        $response = $controller($request);

        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
    }

    public function testRobotsTxtIgnoresRequestPort(): void
    {
        $pageModel = $this->mockClassWithProperties(PageModel::class);

        $pageModelAdapter = $this->mockAdapter(['findPublishedFallbackByHostname']);
        $pageModelAdapter
            ->expects($this->once())
            ->method('findPublishedFallbackByHostname')
            ->with('localhost')
            ->willReturn($pageModel)
        ;

        $framework = $this->mockContaoFramework([PageModel::class => $pageModelAdapter]);
        $framework
            ->expects($this->once())
            ->method('initialize')
        ;

        $eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $eventDispatcher
            ->expects($this->once())
            ->method('dispatch')
        ;

        $request = Request::create('https://localhost:8000/robots.txt');
        $controller = new RobotsTxtController($framework, $eventDispatcher);
        $response = $controller($request);

        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
    }
}
