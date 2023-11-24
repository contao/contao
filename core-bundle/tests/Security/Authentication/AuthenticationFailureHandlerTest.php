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

use Contao\CoreBundle\Security\Authentication\AuthenticationFailureHandler;
use Contao\CoreBundle\Tests\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Security\Core\Exception\AccountStatusException;

class AuthenticationFailureHandlerTest extends TestCase
{
    public function testLogsTheExceptionIfLoggerIsAvailable(): void
    {
        $exception = $this->createMock(AccountStatusException::class);

        $logger = $this->createMock(LoggerInterface::class);
        $logger
            ->expects($this->once())
            ->method('info')
        ;

        $handler = new AuthenticationFailureHandler($logger);
        $response = $handler->onAuthenticationFailure($this->getRequest(), $exception);

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame('https://localhost', $response->getTargetUrl());
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
