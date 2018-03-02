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

use Contao\CoreBundle\Controller\FrontendController;
use Contao\CoreBundle\Security\Authentication\Token\TokenChecker;
use Contao\CoreBundle\Tests\TestCase;
use Contao\PageError403;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Exception\LogoutException;

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
        $framework = $this->mockContaoFramework();

        $framework
            ->expects($this->once())
            ->method('initialize')
        ;

        $router = $this->createMock(RouterInterface::class);

        $router
            ->expects($this->once())
            ->method('generate')
            ->with('contao_root')
            ->willReturn('/')
        ;

        $container = $this->mockContainer();
        $container->set('contao.framework', $framework);
        $container->set('router', $router);

        $controller = new FrontendController();
        $controller->setContainer($container);

        $response = $controller->loginAction();

        $this->assertInstanceOf('Symfony\Component\HttpFoundation\RedirectResponse', $response);
        $this->assertSame('/', $response->getTargetUrl());
    }

    /**
     * @runInSeparateProcess
     */
    public function testRendersTheError403PageUponLogin(): void
    {
        $framework = $this->mockContaoFramework();

        $framework
            ->expects($this->once())
            ->method('initialize')
        ;

        $tokenChecker = $this->createMock(TokenChecker::class);

        $tokenChecker
            ->expects($this->once())
            ->method('hasFrontendUser')
            ->willReturn(true)
        ;

        $tokenChecker
            ->expects($this->once())
            ->method('hasBackendUser')
            ->willReturn(true)
        ;

        $tokenChecker
            ->expects($this->once())
            ->method('isPreviewMode')
            ->willReturn(false)
        ;

        $container = $this->mockContainer();
        $container->set('contao.framework', $framework);
        $container->set('contao.security.token_checker', $tokenChecker);

        $controller = new FrontendController();
        $controller->setContainer($container);

        $GLOBALS['TL_PTY']['error_403'] = PageError403::class;

        $response = $controller->loginAction();

        $this->assertInstanceOf('Symfony\Component\HttpFoundation\Response', $response);
        $this->assertSame(403, $response->getStatusCode());
        $this->assertTrue(\defined('FE_USER_LOGGED_IN'));
        $this->assertTrue(FE_USER_LOGGED_IN);
        $this->assertTrue(\defined('BE_USER_LOGGED_IN'));
        $this->assertFalse(BE_USER_LOGGED_IN);

        unset($GLOBALS['TL_PTY']);
    }

    public function testThrowsALogoutExceptionUponLogout(): void
    {
        $controller = new FrontendController();

        $this->expectException(LogoutException::class);
        $this->expectExceptionMessage('The user was not logged out correctly.');

        $controller->logoutAction();
    }
}
