<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Security\Authentication;

use Contao\CoreBundle\Monolog\ContaoContext;
use Contao\CoreBundle\Security\Authentication\AuthenticationFailureHandler;
use Contao\CoreBundle\Tests\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Security\Core\Exception\AccountStatusException;
use Symfony\Component\Security\Core\User\UserInterface;

class AuthenticationFailureHandlerTest extends TestCase
{
    public function testDoesNotLogAnythingIfNoLogger(): void
    {
        $exception = $this->createMock(AccountStatusException::class);
        $exception
            ->expects($this->never())
            ->method('getUser')
        ;

        $handler = new AuthenticationFailureHandler();

        /** @var RedirectResponse $response */
        $response = $handler->onAuthenticationFailure($this->getRequest(), $exception);

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame('https://localhost', $response->getTargetUrl());
    }

    public function testReadsTheUsernameFromTheException(): void
    {
        $user = $this->createMock(UserInterface::class);
        $user
            ->expects($this->once())
            ->method('getUserIdentifier')
            ->willReturn('foobar')
        ;

        $exception = $this->createMock(AccountStatusException::class);
        $exception
            ->expects($this->once())
            ->method('getUser')
            ->willReturn($user)
        ;

        $context = new ContaoContext(
            'Contao\CoreBundle\Security\Authentication\AuthenticationFailureHandler::logException',
            ContaoContext::ACCESS,
            'foobar'
        );

        $logger = $this->createMock(LoggerInterface::class);
        $logger
            ->expects($this->once())
            ->method('info')
            ->with('', ['contao' => $context])
        ;

        $handler = new AuthenticationFailureHandler($logger);

        /** @var RedirectResponse $response */
        $response = $handler->onAuthenticationFailure($this->getRequest(), $exception);

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame('https://localhost', $response->getTargetUrl());
    }

    public function testDoesNotReadTheUsernameFromTheRequest(): void
    {
        $exception = $this->createMock(AccountStatusException::class);
        $exception
            ->expects($this->once())
            ->method('getUser')
            ->willReturn(null)
        ;

        $context = new ContaoContext(
            'Contao\CoreBundle\Security\Authentication\AuthenticationFailureHandler::logException',
            ContaoContext::ACCESS,
            'anon.'
        );

        $logger = $this->createMock(LoggerInterface::class);
        $logger
            ->expects($this->once())
            ->method('info')
            ->with('', ['contao' => $context])
        ;

        $handler = new AuthenticationFailureHandler($logger);
        $handler->onAuthenticationFailure($this->getRequest(), $exception);
    }

    /**
     * Returns a request object with session.
     */
    private function getRequest(): Request
    {
        $session = $this->createMock(SessionInterface::class);
        $session
            ->expects($this->once())
            ->method('set')
        ;

        $request = $this->createMock(Request::class);
        $request
            ->expects($this->once())
            ->method('getUri')
            ->willReturn('https://localhost')
        ;

        $request
            ->expects($this->once())
            ->method('getSession')
            ->willReturn($session)
        ;

        return $request;
    }
}
