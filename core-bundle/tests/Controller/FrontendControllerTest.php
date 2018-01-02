<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2017 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Tests\Controller;

use Contao\CoreBundle\Controller\FrontendController;
use Contao\CoreBundle\Tests\TestCase;
use Symfony\Component\Routing\RouterInterface;

class FrontendControllerTest extends TestCase
{
    public function testCanBeInstantiated(): void
    {
        $controller = new FrontendController();

        $this->assertInstanceOf('Contao\CoreBundle\Controller\FrontendController', $controller);
    }

    public function testReturnsAResponseInTheActionMethods(): void
    {
        $container = $this->mockContainer();
        $container->set('contao.framework', $this->mockContaoFramework());

        $controller = new FrontendController();
        $controller->setContainer($container);

        $this->assertInstanceOf('Symfony\Component\HttpFoundation\Response', $controller->indexAction());
        $this->assertInstanceOf('Symfony\Component\HttpFoundation\Response', $controller->cronAction());
        $this->assertInstanceOf('Symfony\Component\HttpFoundation\RedirectResponse', $controller->shareAction());
    }

    public function testRedirectsToTheRootPageUponLogin(): void
    {
        $router = $this->createMock(RouterInterface::class);

        $router
            ->expects($this->once())
            ->method('generate')
            ->with('contao_root')
            ->willReturn('/')
        ;

        $container = $this->mockContainer();
        $container->set('contao.framework', $this->mockContaoFramework());
        $container->set('router', $router);

        $controller = new FrontendController();
        $controller->setContainer($container);

        $response = $controller->loginAction();

        $this->assertInstanceOf('Symfony\Component\HttpFoundation\RedirectResponse', $response);
        $this->assertSame('/', $response->getTargetUrl());
    }

    public function testRedirectsToTheRootPageUponLogout(): void
    {
        $router = $this->createMock(RouterInterface::class);

        $router
            ->expects($this->once())
            ->method('generate')
            ->with('contao_root')
            ->willReturn('/')
        ;

        $container = $this->mockContainer();
        $container->set('contao.framework', $this->mockContaoFramework());
        $container->set('router', $router);

        $controller = new FrontendController();
        $controller->setContainer($container);

        $response = $controller->logoutAction();

        $this->assertInstanceOf('Symfony\Component\HttpFoundation\RedirectResponse', $response);
        $this->assertSame('/', $response->getTargetUrl());
    }
}
