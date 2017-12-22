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

use Contao\BackendUser;
use Contao\CoreBundle\Framework\ContaoFrameworkInterface;
use Contao\CoreBundle\Security\Authentication\AuthenticationSuccessHandler;
use Contao\CoreBundle\Tests\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Http\HttpUtils;
use Symfony\Component\Translation\TranslatorInterface;

class AuthenticationSuccessHandlerTest extends TestCase
{
    public function testCanBeInstantiated(): void
    {
        $handler = $this->mockSuccessHandler();

        $this->assertInstanceOf('Contao\CoreBundle\Security\Authentication\AuthenticationSuccessHandler', $handler);
    }

    public function testUpdatesTheUser(): void
    {
        $framework = $this->mockContaoFramework();

        $framework
            ->expects($this->once())
            ->method('initialize')
        ;

        $logger = $this->createMock(LoggerInterface::class);

        $logger
            ->expects($this->once())
            ->method('info')
            ->with('User "foobar" has logged in')
        ;

        /** @var BackendUser|\PHPUnit_Framework_MockObject_MockObject $user */
        $user = $this->createPartialMock(BackendUser::class, ['save']);
        $user->username = 'foobar';
        $user->lastLogin = time() - 3600;
        $user->currentLogin = time() - 1800;
        $user->language = '';

        $user
            ->expects($this->once())
            ->method('save')
        ;

        $token = $this->createMock(TokenInterface::class);

        $token
            ->expects($this->once())
            ->method('getUser')
            ->willReturn($user)
        ;

        $handler = $this->mockSuccessHandler($framework, $logger);
        $handler->onAuthenticationSuccess(new Request(), $token);
    }

    public function testDoesNotUpdateTheUserIfNotAContaoUser(): void
    {
        $framework = $this->mockContaoFramework();

        $framework
            ->expects($this->never())
            ->method('initialize')
        ;

        $token = $this->createMock(TokenInterface::class);

        $token
            ->expects($this->once())
            ->method('getUser')
            ->willReturn($this->createMock(UserInterface::class))
        ;

        $handler = $this->mockSuccessHandler($framework);
        $handler->onAuthenticationSuccess(new Request(), $token);
    }

    public function testSetsTheLocale(): void
    {
        $framework = $this->mockContaoFramework();

        $framework
            ->expects($this->once())
            ->method('initialize')
        ;

        /** @var BackendUser|\PHPUnit_Framework_MockObject_MockObject $user */
        $user = $this->createPartialMock(BackendUser::class, ['save']);
        $user->username = 'foobar';
        $user->lastLogin = time() - 3600;
        $user->currentLogin = time() - 1800;
        $user->language = 'de';

        $user
            ->expects($this->once())
            ->method('save')
        ;

        $session = $this->createMock(SessionInterface::class);

        $session
            ->expects($this->once())
            ->method('isStarted')
            ->willReturn(true)
        ;

        $session
            ->expects($this->once())
            ->method('set')
            ->with('_locale', 'de')
        ;

        $request = $this->createMock(Request::class);

        $request
            ->expects($this->once())
            ->method('getSession')
            ->willReturn($session)
        ;

        $request
            ->expects($this->once())
            ->method('setLocale')
            ->with('de')
        ;

        $token = $this->createMock(TokenInterface::class);

        $token
            ->expects($this->once())
            ->method('getUser')
            ->willReturn($user)
        ;

        $GLOBALS['TL_LANGUAGE'] = 'en';

        $handler = $this->mockSuccessHandler($framework);
        $handler->onAuthenticationSuccess($request, $token);

        $this->assertSame('de', $GLOBALS['TL_LANGUAGE']);

        unset($GLOBALS['TL_LANGUAGE']);
    }

    /**
     * Mocks an authentication success handler.
     *
     * @param ContaoFrameworkInterface|null $framework
     * @param LoggerInterface|null          $logger
     *
     * @return AuthenticationSuccessHandler
     */
    private function mockSuccessHandler(ContaoFrameworkInterface $framework = null, LoggerInterface $logger = null): AuthenticationSuccessHandler
    {
        $utils = $this->createMock(HttpUtils::class);

        $utils
            ->method('createRedirectResponse')
            ->willReturn(new RedirectResponse('http://localhost'))
        ;

        if (null === $framework) {
            $framework = $this->mockContaoFramework();
        }

        $translator = $this->createMock(TranslatorInterface::class);

        if (null === $logger) {
            $logger = $this->createMock(LoggerInterface::class);
        }

        return new AuthenticationSuccessHandler($utils, $framework, $translator, $logger);
    }
}
