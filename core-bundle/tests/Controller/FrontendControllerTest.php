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
use Contao\CoreBundle\Cron\Cron;
use Contao\CoreBundle\Csrf\ContaoCsrfTokenManager;
use Contao\CoreBundle\Tests\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Exception\LogoutException;

class FrontendControllerTest extends TestCase
{
    public function testThrowsALogoutExceptionUponLogout(): never
    {
        $controller = new FrontendController();

        $this->expectException(LogoutException::class);
        $this->expectExceptionMessage('The user was not logged out correctly.');

        $controller->logoutAction();
    }

    public function testCheckCookiesAction(): void
    {
        $controller = new FrontendController();
        $response = $controller->checkCookiesAction();

        $this->assertTrue($response->headers->hasCacheControlDirective('private'));
        $this->assertTrue($response->headers->hasCacheControlDirective('no-store'));
        $this->assertTrue($response->headers->hasCacheControlDirective('must-revalidate'));
        $this->assertSame('image/png', $response->headers->get('Content-Type'));
    }

    public function testRequestTokenScriptAction(): void
    {
        $tokenManager = $this->createMock(ContaoCsrfTokenManager::class);
        $tokenManager
            ->expects($this->once())
            ->method('getDefaultTokenValue')
            ->willReturn('tokenValue')
        ;

        $container = $this->getContainerWithContaoConfiguration();
        $container->set('contao.csrf.token_manager', $tokenManager);

        $controller = new FrontendController();
        $controller->setContainer($container);

        $response = $controller->requestTokenScriptAction();

        $this->assertTrue($response->headers->hasCacheControlDirective('private'));
        $this->assertTrue($response->headers->hasCacheControlDirective('no-store'));
        $this->assertTrue($response->headers->hasCacheControlDirective('must-revalidate'));
        $this->assertSame('application/javascript; charset=UTF-8', $response->headers->get('Content-Type'));
        $this->assertSame('document.querySelectorAll(\'input[name=REQUEST_TOKEN],input[name$="[REQUEST_TOKEN]"]\').forEach(function(i){i.value="tokenValue"})', $response->getContent());
    }

    public function testRunsTheCronJobsUponGetRequests(): void
    {
        $framework = $this->mockContaoFramework();

        $cron = $this->createMock(Cron::class);
        $cron
            ->expects($this->once())
            ->method('run')
            ->with(Cron::SCOPE_WEB)
        ;

        $container = $this->getContainerWithContaoConfiguration();
        $container->set('contao.framework', $framework);
        $container->set('contao.cron', $cron);

        $controller = new FrontendController();
        $controller->setContainer($container);

        $request = $this->createMock(Request::class);
        $request
            ->expects($this->once())
            ->method('isMethod')
            ->with(Request::METHOD_GET)
            ->willReturn(true)
        ;

        $controller->cronAction($request);
    }

    public function testDoesNotRunTheCronJobsUponPostRequests(): void
    {
        $framework = $this->mockContaoFramework();

        $cron = $this->createMock(Cron::class);
        $cron
            ->expects($this->never())
            ->method('run')
        ;

        $container = $this->getContainerWithContaoConfiguration();
        $container->set('contao.framework', $framework);
        $container->set('contao.cron', $cron);

        $controller = new FrontendController();
        $controller->setContainer($container);

        $request = $this->createMock(Request::class);
        $request
            ->expects($this->once())
            ->method('isMethod')
            ->with(Request::METHOD_GET)
            ->willReturn(false)
        ;

        $controller->cronAction($request);
    }
}
