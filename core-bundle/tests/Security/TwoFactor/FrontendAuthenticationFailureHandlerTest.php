<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Security\TwoFactor;

use Contao\CoreBundle\Security\TwoFactor\FrontendAuthenticationFailureHandler;
use Contao\CoreBundle\Tests\TestCase;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Http\HttpUtils;

class FrontendAuthenticationFailureHandlerTest extends TestCase
{
    public function testRedirectsOnAuthenticationFailure(): void
    {
        $utils = $this->createMock(HttpUtils::class);
        $exception = $this->createMock(AuthenticationException::class);
        $session = $this->createMock(SessionInterface::class);
        $session
            ->expects($this->once())
            ->method('set')
        ;

        $request = new Request();
        $request->headers->set(Security::AUTHENTICATION_ERROR, $exception);
        $request->headers->set('referer', 'http://localhost');

        $request->setSession($session);

        $utils
            ->expects($this->once())
            ->method('createRedirectResponse')
            ->willReturn(new RedirectResponse('http://localhost'))
        ;

        $handler = new FrontendAuthenticationFailureHandler($utils);
        $handler->onAuthenticationFailure($request, $exception);
    }
}
