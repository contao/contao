<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2017 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Test\Security;

use Contao\CoreBundle\Security\LogoutSuccessHandler;
use Contao\CoreBundle\Tests\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\RouterInterface;

class LogoutSuccessHandlerTest extends TestCase
{
    /**
     * @var RouterInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $router;

    /**
     * @var SessionInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $session;

    /**
     * @var Request
     */
    private $request;

    /**
     * {@inheritdoc}
     */
    public function setUp(): void
    {
        $this->router = $this->createMock(RouterInterface::class);
        $this->session = $this->createMock(SessionInterface::class);
        $this->request = new Request();
    }

    public function testCanBeInstantiated(): void
    {
        $handler = new LogoutSuccessHandler($this->router, $this->mockScopeMatcher());

        $this->assertInstanceOf('Contao\CoreBundle\Security\LogoutSuccessHandler', $handler);
    }

    public function testRedirectsToTheDefaultTargetIfThereIsNoSession(): void
    {
        $this->router
            ->expects($this->once())
            ->method('generate')
            ->with('contao_root')
            ->willReturn('/')
        ;

        $handler = new LogoutSuccessHandler($this->router, $this->mockScopeMatcher());

        $this->assertInstanceOf('Symfony\Component\HttpFoundation\RedirectResponse', $handler->onLogoutSuccess($this->request));
    }

    public function testRedirectsToTheDefaultTargetIfThereIsNoLogoutTarget(): void
    {
        $this->session
            ->expects($this->once())
            ->method('has')
            ->willReturn(false)
        ;

        $this->request->setSession($this->session);

        $this->router
            ->expects($this->once())
            ->method('generate')
            ->with('contao_root')
            ->willReturn('/')
        ;

        $handler = new LogoutSuccessHandler($this->router, $this->mockScopeMatcher());

        $this->assertInstanceOf('Symfony\Component\HttpFoundation\RedirectResponse', $handler->onLogoutSuccess($this->request));
    }

    public function testRedirectsToTheBackendLogin(): void
    {
        $this->request->attributes->set('_scope', 'backend');

        $this->session
            ->expects($this->once())
            ->method('has')
            ->willReturn(false)
        ;

        $this->request->setSession($this->session);

        $this->router
            ->expects($this->once())
            ->method('generate')
            ->with('contao_backend_login')
            ->willReturn('/contao/login')
        ;

        $handler = new LogoutSuccessHandler($this->router, $this->mockScopeMatcher());

        $this->assertInstanceOf('Symfony\Component\HttpFoundation\RedirectResponse', $handler->onLogoutSuccess($this->request));
    }

    public function testRedirectsToTheLogoutTarget(): void
    {
        $this->session
            ->expects($this->once())
            ->method('has')
            ->with('_contao_logout_target')
            ->willReturn(true)
        ;

        $this->session
            ->expects($this->once())
            ->method('get')
            ->with('_contao_logout_target')
            ->willReturn('/')
        ;

        $this->request->setSession($this->session);

        $handler = new LogoutSuccessHandler($this->router, $this->mockScopeMatcher());

        $this->assertInstanceOf('Symfony\Component\HttpFoundation\RedirectResponse', $handler->onLogoutSuccess($this->request));
    }
}
