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
use Contao\FrontendUser;
use Contao\User;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Attribute\AttributeBagInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Http\HttpUtils;

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
        $user->session = null;

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

    /**
     * @param string      $class
     * @param string|null $key
     *
     * @dataProvider getSessionData
     */
    public function testReplacesTheSessionData(string $class, ?string $key): void
    {
        $framework = $this->mockContaoFramework();

        $framework
            ->expects($this->once())
            ->method('initialize')
        ;

        /** @var BackendUser|\PHPUnit_Framework_MockObject_MockObject $user */
        $user = $this->createPartialMock($class, ['save']);
        $user->lastLogin = time() - 3600;
        $user->currentLogin = time() - 1800;
        $user->session = ['tl_page' => [1]];

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

        $bag = $this->createMock(AttributeBagInterface::class);

        $bag
            ->expects($key ? $this->once() : $this->never())
            ->method('replace')
            ->with(['tl_page' => [1]])
        ;

        $session = $this->createMock(SessionInterface::class);

        $session
            ->expects($key ? $this->once() : $this->never())
            ->method('getBag')
            ->with($key)
            ->willReturn($bag)
        ;

        $request = $this->createMock(Request::class);

        $request
            ->expects($this->once())
            ->method('getSession')
            ->willReturn($session)
        ;

        $handler = $this->mockSuccessHandler($framework);

        if (null === $key) {
            $this->expectException('RuntimeException');
            $this->expectExceptionMessage(sprintf('Unsupported user class "%s".', \get_class($user)));
        }

        $handler->onAuthenticationSuccess($request, $token);
    }

    /**
     * @return array
     */
    public function getSessionData(): array
    {
        return [
            [BackendUser::class, 'contao_backend'],
            [FrontendUser::class, 'contao_frontend'],
            [User::class, null],
        ];
    }

    public function testFailsToReplaceTheSessionDataIfThereIsNoSession(): void
    {
        $framework = $this->mockContaoFramework();

        $framework
            ->expects($this->once())
            ->method('initialize')
        ;

        /** @var BackendUser|\PHPUnit_Framework_MockObject_MockObject $user */
        $user = $this->createPartialMock(BackendUser::class, ['save']);
        $user->lastLogin = time() - 3600;
        $user->currentLogin = time() - 1800;
        $user->session = ['tl_page' => [1]];

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

        $handler = $this->mockSuccessHandler($framework);

        $this->expectException('RuntimeException');
        $this->expectExceptionMessage('The request did not contain a session.');

        $handler->onAuthenticationSuccess(new Request(), $token);
    }

    /**
     * @group legacy
     *
     * @expectedDeprecation Using the "postLogin" hook has been deprecated %s.
     */
    public function testTriggersThePostLoginHook(): void
    {
        $framework = $this->mockContaoFramework();

        $framework
            ->expects($this->once())
            ->method('initialize')
        ;

        $framework
            ->expects($this->once())
            ->method('createInstance')
            ->with(__CLASS__)
            ->willReturn($this)
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

        $GLOBALS['TL_HOOKS']['postLogin'] = [[__CLASS__, 'onPostLogin']];

        $handler = $this->mockSuccessHandler($framework, $logger);
        $handler->onAuthenticationSuccess(new Request(), $token);

        unset($GLOBALS['TL_HOOKS']);
    }

    /**
     * @param UserInterface $user
     */
    public function onPostLogin(UserInterface $user): void
    {
        $this->assertInstanceOf('Contao\BackendUser', $user);
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

        if (null === $logger) {
            $logger = $this->createMock(LoggerInterface::class);
        }

        return new AuthenticationSuccessHandler($utils, $framework, $logger);
    }
}
