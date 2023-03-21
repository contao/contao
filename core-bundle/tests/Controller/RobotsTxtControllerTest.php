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
use Contao\CoreBundle\Routing\PageFinder;
use Contao\CoreBundle\Tests\TestCase;
use Contao\PageModel;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class RobotsTxtControllerTest extends TestCase
{
    public function testThrowsNotFoundHttpExceptionIfNoRootPageFound(): void
    {
        $request = Request::create('https://www.example.org/robots.txt');

        $pageFinder = $this->createMock(PageFinder::class);
        $pageFinder
            ->expects($this->once())
            ->method('findRootPageForHost')
            ->with('www.example.org')
            ->willReturn(null)
        ;

        $eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $eventDispatcher
            ->expects($this->never())
            ->method('dispatch')
        ;

        $this->expectException(NotFoundHttpException::class);

        $controller = new RobotsTxtController($pageFinder, $eventDispatcher);
        $controller($request);
    }

    public function testRobotsTxt(): void
    {
        $request = Request::create('https://www.example.org/robots.txt');

        $pageModel = $this->mockClassWithProperties(PageModel::class);

        $pageFinder = $this->createMock(PageFinder::class);
        $pageFinder
            ->expects($this->once())
            ->method('findRootPageForHost')
            ->with('www.example.org')
            ->willReturn($pageModel)
        ;

        $eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $eventDispatcher
            ->expects($this->once())
            ->method('dispatch')
        ;

        $controller = new RobotsTxtController($pageFinder, $eventDispatcher);
        $response = $controller($request);

        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
    }

    public function testRobotsTxtIgnoresRequestPort(): void
    {
        $request = Request::create('https://localhost:8000/robots.txt');

        $pageModel = $this->mockClassWithProperties(PageModel::class);

        $pageFinder = $this->createMock(PageFinder::class);
        $pageFinder
            ->expects($this->once())
            ->method('findRootPageForHost')
            ->with('localhost')
            ->willReturn($pageModel)
        ;

        $eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $eventDispatcher
            ->expects($this->once())
            ->method('dispatch')
        ;

        $controller = new RobotsTxtController($pageFinder, $eventDispatcher);
        $response = $controller($request);

        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
    }
}
