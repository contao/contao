<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2017 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Tests\Security\Authentication;

use Contao\CoreBundle\Monolog\ContaoContext;
use Contao\CoreBundle\Security\Authentication\AuthenticationFailureHandler;
use Contao\CoreBundle\Tests\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpKernel\HttpKernel;
use Symfony\Component\Security\Core\Exception\AccountStatusException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Http\HttpUtils;

class AuthenticationFailureHandlerTest extends TestCase
{
    public function testCanBeInstantiated(): void
    {
        $handler = new AuthenticationFailureHandler(
            $this->createMock(HttpKernel::class),
            $this->createMock(HttpUtils::class)
        );

        $this->assertInstanceOf('Contao\CoreBundle\Security\Authentication\AuthenticationFailureHandler', $handler);
    }

    public function testDoesNotLogAnythingIfNoLogger(): void
    {
        $exception = $this->createMock(AccountStatusException::class);

        $exception
            ->expects($this->never())
            ->method('getUser')
        ;

        $kernel = $this->createMock(HttpKernel::class);
        $utils = $this->createMock(HttpUtils::class);

        $utils
            ->expects($this->once())
            ->method('createRedirectResponse')
            ->willReturn(new RedirectResponse('http://localhost'))
        ;

        $handler = new AuthenticationFailureHandler($kernel, $utils);
        $handler->onAuthenticationFailure($this->getRequest(), $exception);
    }

    public function testReadsTheUsernameFromTheException(): void
    {
        $user = $this->createMock(UserInterface::class);

        $user
            ->expects($this->once())
            ->method('getUsername')
            ->willReturn('foobar')
        ;

        $exception = $this->createMock(AccountStatusException::class);

        $exception
            ->expects($this->once())
            ->method('getUser')
            ->willReturn($user)
        ;

        $kernel = $this->createMock(HttpKernel::class);
        $utils = $this->createMock(HttpUtils::class);

        $utils
            ->expects($this->once())
            ->method('createRedirectResponse')
            ->willReturn(new RedirectResponse('http://localhost'))
        ;

        $context = new ContaoContext(
            'Contao\CoreBundle\Security\Authentication\AuthenticationFailureHandler::onAuthenticationFailure',
            ContaoContext::ACCESS,
            'foobar'
        );

        $logger = $this->createMock(LoggerInterface::class);

        $logger
            ->expects($this->once())
            ->method('info')
            ->with('', ['contao' => $context])
        ;

        $handler = new AuthenticationFailureHandler($kernel, $utils, [], $logger);
        $handler->onAuthenticationFailure($this->getRequest(), $exception);
    }

    public function testReadsTheUsernameFromTheRequest(): void
    {
        $exception = $this->createMock(AccountStatusException::class);

        $exception
            ->expects($this->once())
            ->method('getUser')
            ->willReturn(null)
        ;

        $kernel = $this->createMock(HttpKernel::class);
        $utils = $this->createMock(HttpUtils::class);

        $utils
            ->expects($this->once())
            ->method('createRedirectResponse')
            ->willReturn(new RedirectResponse('http://localhost'))
        ;

        $context = new ContaoContext(
            'Contao\CoreBundle\Security\Authentication\AuthenticationFailureHandler::onAuthenticationFailure',
            ContaoContext::ACCESS,
            'barfoo'
        );

        $logger = $this->createMock(LoggerInterface::class);

        $logger
            ->expects($this->once())
            ->method('info')
            ->with('', ['contao' => $context])
        ;

        $handler = new AuthenticationFailureHandler($kernel, $utils, [], $logger);
        $handler->onAuthenticationFailure($this->getRequest(), $exception);
    }

    /**
     * Returns a request object with session.
     *
     * @return Request
     */
    private function getRequest(): Request
    {
        $session = $this->createMock(SessionInterface::class);

        $session
            ->expects($this->once())
            ->method('set')
        ;

        $request = new Request();
        $request->request->set('username', 'barfoo');
        $request->setSession($session);

        return $request;
    }
}
