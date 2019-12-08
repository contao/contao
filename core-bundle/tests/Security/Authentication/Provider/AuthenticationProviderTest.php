<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Security\Authentication\Provider;

use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Security\Authentication\Provider\AuthenticationProvider;
use Contao\CoreBundle\Security\Exception\LockedException;
use Contao\CoreBundle\Tests\TestCase;
use Contao\FrontendUser;
use Contao\System;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Encoder\EncoderFactoryInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

class AuthenticationProviderTest extends TestCase
{
    public function testHandlesContaoUsers(): void
    {
        /** @var FrontendUser&MockObject $user */
        $user = $this->createPartialMock(FrontendUser::class, ['getPassword', 'save']);
        $user->username = 'foo';
        $user->loginCount = 3;

        $user
            ->expects($this->once())
            ->method('getPassword')
            ->willReturn('foobar')
        ;

        $user
            ->expects($this->once())
            ->method('save')
        ;

        $currentUser = $this->createMock(UserInterface::class);
        $currentUser
            ->expects($this->once())
            ->method('getPassword')
            ->willReturn('barfoo')
        ;

        $token = $this->createMock(UsernamePasswordToken::class);
        $token
            ->expects($this->once())
            ->method('getUser')
            ->willReturn($currentUser)
        ;

        $provider = $this->getProvider();

        $this->expectException(BadCredentialsException::class);
        $this->expectExceptionMessage('Invalid password submitted for username "foo"');

        $provider->checkAuthentication($user, $token);
    }

    public function testDoesNotHandleNonContaoUsers(): void
    {
        $user = $this->createMock(UserInterface::class);
        $user
            ->expects($this->once())
            ->method('getPassword')
            ->willReturn('foobar')
        ;

        $currentUser = $this->createMock(UserInterface::class);
        $currentUser
            ->expects($this->once())
            ->method('getPassword')
            ->willReturn('foobar')
        ;

        $token = $this->createMock(UsernamePasswordToken::class);
        $token
            ->expects($this->once())
            ->method('getUser')
            ->willReturn($currentUser)
        ;

        $provider = $this->getProvider();
        $provider->checkAuthentication($user, $token);

        $this->addToAssertionCount(1); // does not throw an exception
    }

    public function testLocksAUserAfterThreeFailedLoginAttempts(): void
    {
        /** @var FrontendUser&MockObject $user */
        $user = $this->createPartialMock(FrontendUser::class, ['getPassword', 'save']);
        $user->username = 'foo';
        $user->locked = 0;
        $user->loginCount = 1;
        $user->name = 'Admin';
        $user->firstname = 'Foo';
        $user->lastname = 'Bar';

        $user
            ->expects($this->once())
            ->method('getPassword')
            ->willReturn('foobar')
        ;

        $user
            ->expects($this->once())
            ->method('save')
        ;

        $currentUser = $this->createMock(UserInterface::class);
        $currentUser
            ->expects($this->once())
            ->method('getPassword')
            ->willReturn('barfoo')
        ;

        $token = $this->createMock(UsernamePasswordToken::class);
        $token
            ->expects($this->once())
            ->method('getUser')
            ->willReturn($currentUser)
        ;

        $framework = $this->mockContaoFramework();
        $framework
            ->expects($this->atLeastOnce())
            ->method('initialize')
        ;

        $provider = $this->getProvider($framework);

        $this->expectException(LockedException::class);
        $this->expectExceptionMessage('User "foo" has been locked for 5 minutes');

        $provider->checkAuthentication($user, $token);
    }

    public function testOnlyHandlesBadCredentialsExceptions(): void
    {
        $token = $this->createMock(UsernamePasswordToken::class);
        $token
            ->expects($this->once())
            ->method('getUser')
            ->willThrowException(new AuthenticationException('Unsupported user'))
        ;

        $provider = $this->getProvider();

        $this->expectException(AuthenticationException::class);
        $this->expectExceptionMessage('Unsupported user');

        $provider->checkAuthentication($this->createMock(FrontendUser::class), $token);
    }

    /**
     * @group legacy
     * @dataProvider getCheckCredentialsHookData
     *
     * @expectedDeprecation Using the "checkCredentials" hook has been deprecated %s.
     */
    public function testTriggersTheCheckCredentialsHook(string $callback): void
    {
        /** @var FrontendUser&MockObject $user */
        $user = $this->createPartialMock(FrontendUser::class, ['getPassword', 'save']);
        $user->username = 'foo';
        $user->loginCount = 3;

        $user
            ->expects($this->once())
            ->method('getPassword')
            ->willReturn('foobar')
        ;

        $user
            ->expects($this->once())
            ->method('save')
        ;

        $currentUser = $this->createMock(UserInterface::class);
        $currentUser
            ->expects($this->once())
            ->method('getPassword')
            ->willReturn('barfoo')
        ;

        $token = $this->createMock(UsernamePasswordToken::class);
        $token
            ->expects($this->once())
            ->method('getUser')
            ->willReturn($currentUser)
        ;

        $token
            ->expects($this->once())
            ->method('getUsername')
            ->willReturn('foo')
        ;

        $token
            ->expects($this->once())
            ->method('getCredentials')
            ->willReturn('bar')
        ;

        $systemAdapter = $this->mockAdapter(['importStatic']);
        $systemAdapter
            ->method('importStatic')
            ->with(static::class)
            ->willReturn($this)
        ;

        $framework = $this->mockContaoFramework([System::class => $systemAdapter]);
        $framework
            ->expects($this->atLeastOnce())
            ->method('initialize')
        ;

        $GLOBALS['TL_HOOKS']['checkCredentials'][] = [static::class, $callback];

        $provider = $this->getProvider($framework);

        if ('onInvalidCredentials' === $callback) {
            $this->expectException(BadCredentialsException::class);
            $this->expectExceptionMessage('Invalid password submitted for username "foo"');
        }

        $provider->checkAuthentication($user, $token);

        unset($GLOBALS['TL_HOOKS']);
    }

    public function getCheckCredentialsHookData(): \Generator
    {
        yield ['onValidCredentials'];
        yield ['onInvalidCredentials'];
    }

    public function onValidCredentials($username): bool
    {
        return true;
    }

    public function onInvalidCredentials($username): bool
    {
        return false;
    }

    /**
     * @param ContaoFramework&MockObject $framework
     */
    private function getProvider(ContaoFramework $framework = null): AuthenticationProvider
    {
        $userProvider = $this->createMock(UserProviderInterface::class);
        $userChecker = $this->createMock(UserCheckerInterface::class);
        $providerKey = 'contao_frontend';
        $encoderFactory = $this->createMock(EncoderFactoryInterface::class);

        if (null === $framework) {
            $framework = $this->createMock(ContaoFramework::class);
        }

        return new AuthenticationProvider($userProvider, $userChecker, $providerKey, $encoderFactory, $framework);
    }
}
