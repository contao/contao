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
use Contao\CoreBundle\Fixtures\Controller\PageError401Controller;
use Contao\CoreBundle\Fixtures\Exception\PageError401Exception;
use Contao\CoreBundle\Tests\TestCase;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\Security\Core\Exception\LogoutException;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

class FrontendControllerTest extends TestCase
{
    public function testThrowsAnExceptionUponLoginIfThereIsNoError401Page(): void
    {
        $framework = $this->mockContaoFramework();
        $framework
            ->expects($this->once())
            ->method('initialize')
        ;

        $container = $this->getContainerWithContaoConfiguration();
        $container->set('contao.framework', $framework);

        $controller = new FrontendController();
        $controller->setContainer($container);

        $this->expectException(UnauthorizedHttpException::class);

        $controller->loginAction();
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testRendersTheError401PageUponLogin(): void
    {
        $framework = $this->mockContaoFramework();
        $framework
            ->expects($this->once())
            ->method('initialize')
        ;

        $container = $this->getContainerWithContaoConfiguration();
        $container->set('contao.framework', $framework);

        $controller = new FrontendController();
        $controller->setContainer($container);

        $GLOBALS['TL_PTY']['error_401'] = PageError401Controller::class;

        $response = $controller->loginAction();

        $this->assertSame(401, $response->getStatusCode());

        unset($GLOBALS['TL_PTY']);
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testThrowsAnExceptionUponLoginIfTheError401PageThrowsAnException(): void
    {
        $framework = $this->mockContaoFramework();
        $framework
            ->expects($this->once())
            ->method('initialize')
        ;

        $container = $this->getContainerWithContaoConfiguration();
        $container->set('contao.framework', $framework);

        $controller = new FrontendController();
        $controller->setContainer($container);

        $GLOBALS['TL_PTY']['error_401'] = PageError401Exception::class;

        $this->expectException(UnauthorizedHttpException::class);

        $controller->loginAction();

        unset($GLOBALS['TL_PTY']);
    }

    public function testThrowsALogoutExceptionUponLogout(): void
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
        $bag = new ParameterBag();
        $bag->set('contao.csrf_token_name', 'csrf_token');

        $token = $this->createMock(CsrfToken::class);
        $token
            ->expects($this->once())
            ->method('getValue')
            ->willReturn('tokenValue')
        ;

        $tokenManager = $this->createMock(CsrfTokenManagerInterface::class);
        $tokenManager
            ->expects($this->once())
            ->method('getToken')
            ->with('csrf_token')
            ->willReturn($token)
        ;

        $container = $this->getContainerWithContaoConfiguration();
        $container->set('parameter_bag', $bag);
        $container->set('contao.csrf.token_manager', $tokenManager);

        $controller = new FrontendController();
        $controller->setContainer($container);

        $response = $controller->requestTokenScriptAction();

        $this->assertTrue($response->headers->hasCacheControlDirective('private'));
        $this->assertTrue($response->headers->hasCacheControlDirective('no-store'));
        $this->assertTrue($response->headers->hasCacheControlDirective('must-revalidate'));
        $this->assertSame('application/javascript; charset=UTF-8', $response->headers->get('Content-Type'));
        $this->assertSame('document.querySelectorAll("input[name=REQUEST_TOKEN]").forEach(function(i){i.value="tokenValue"})', $response->getContent());
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testRendersTheError401PageForTwoFactorRoute(): void
    {
        $framework = $this->mockContaoFramework();
        $framework
            ->expects($this->once())
            ->method('initialize')
        ;

        $container = $this->getContainerWithContaoConfiguration();
        $container->set('contao.framework', $framework);

        $controller = new FrontendController();
        $controller->setContainer($container);

        $GLOBALS['TL_PTY']['error_401'] = PageError401Controller::class;

        $response = $controller->twoFactorAuthenticationAction();

        $this->assertSame(401, $response->getStatusCode());

        unset($GLOBALS['TL_PTY']);
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testThrowsUnauthorizedHttpExceptionIfNoError401PageTypeIsAvailableForTwoFactorRoute(): void
    {
        $framework = $this->mockContaoFramework();
        $framework
            ->expects($this->once())
            ->method('initialize')
        ;

        $container = $this->getContainerWithContaoConfiguration();
        $container->set('contao.framework', $framework);

        $controller = new FrontendController();
        $controller->setContainer($container);

        $this->expectException(UnauthorizedHttpException::class);
        $this->expectExceptionMessage('Not authorized');

        $controller->twoFactorAuthenticationAction();
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testThrowsAnExceptionUponTwoFactorAuthenticationIfTheError401PageThrowsAnException(): void
    {
        $framework = $this->mockContaoFramework();
        $framework
            ->expects($this->once())
            ->method('initialize')
        ;

        $container = $this->getContainerWithContaoConfiguration();
        $container->set('contao.framework', $framework);

        $controller = new FrontendController();
        $controller->setContainer($container);

        $GLOBALS['TL_PTY']['error_401'] = PageError401Exception::class;

        $this->expectException(UnauthorizedHttpException::class);

        $controller->twoFactorAuthenticationAction();

        unset($GLOBALS['TL_PTY']);
    }

    public function testRunsCronOnGetRequest(): void
    {
        $framework = $this->mockContaoFramework();

        $container = $this->getContainerWithContaoConfiguration();
        $container->set('contao.framework', $framework);

        $controller = new FrontendController();
        $controller->setContainer($container);

        $cron = $this->createMock(Cron::class);
        $cron
            ->expects($this->once())
            ->method('run')
            ->with([Cron::SCOPE_WEB])
        ;

        $request = $this->createMock(Request::class);
        $request
            ->expects($this->once())
            ->method('isMethod')
            ->with(Request::METHOD_GET)
            ->willReturn(true)
        ;

        $controller->cronAction($request, $cron);
    }

    public function testDoesNotRunCronOnPostRequest(): void
    {
        $framework = $this->mockContaoFramework();

        $container = $this->getContainerWithContaoConfiguration();
        $container->set('contao.framework', $framework);

        $controller = new FrontendController();
        $controller->setContainer($container);

        $cron = $this->createMock(Cron::class);
        $cron
            ->expects($this->never())
            ->method('run')
        ;

        $request = $this->createMock(Request::class);
        $request
            ->expects($this->once())
            ->method('isMethod')
            ->with(Request::METHOD_GET)
            ->willReturn(false)
        ;

        $controller->cronAction($request, $cron);
    }
}
