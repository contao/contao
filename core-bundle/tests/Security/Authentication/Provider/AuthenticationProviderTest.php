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

use Contao\BackendUser;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Security\Authentication\Provider\AuthenticationProvider;
use Contao\CoreBundle\Security\Exception\LockedException;
use Contao\CoreBundle\Tests\TestCase;
use Contao\FrontendUser;
use Contao\System;
use Scheb\TwoFactorBundle\Security\Authentication\Exception\InvalidTwoFactorCodeException;
use Scheb\TwoFactorBundle\Security\Authentication\Token\TwoFactorTokenInterface;
use Scheb\TwoFactorBundle\Security\TwoFactor\AuthenticationContext;
use Scheb\TwoFactorBundle\Security\TwoFactor\AuthenticationContextFactoryInterface;
use Scheb\TwoFactorBundle\Security\TwoFactor\Handler\AuthenticationHandlerInterface;
use Scheb\TwoFactorBundle\Security\TwoFactor\Trusted\TrustedDeviceManagerInterface;
use Symfony\Bridge\PhpUnit\ClockMock;
use Symfony\Bridge\PhpUnit\ExpectDeprecationTrait;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactoryInterface;
use Symfony\Component\Security\Core\Authentication\Provider\AuthenticationProviderInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

class AuthenticationProviderTest extends TestCase
{
    use ExpectDeprecationTrait;

    protected function tearDown(): void
    {
        unset($GLOBALS['TL_HOOKS']);

        parent::tearDown();
    }

    public function testAuthenticatesTwoFactorToken(): void
    {
        $user = $this->createPartialMock(FrontendUser::class, []);

        $token = $this->createMock(TwoFactorTokenInterface::class);
        $token
            ->expects($this->once())
            ->method('getUser')
            ->willReturn($user)
        ;

        $authProvider = $this->createMock(AuthenticationProviderInterface::class);
        $authProvider
            ->expects($this->once())
            ->method('authenticate')
            ->with($token)
            ->willReturn($token)
        ;

        $userChecker = $this->createMock(UserCheckerInterface::class);
        $userChecker
            ->expects($this->once())
            ->method('checkPreAuth')
            ->with($user)
        ;

        $userChecker
            ->expects($this->once())
            ->method('checkPostAuth')
            ->with($user)
        ;

        $provider = $this->createTwoFactorProvider($authProvider, $userChecker);
        $provider->authenticate($token);
    }

    /**
     * @dataProvider invalidTwoFactorCodeProvider
     */
    public function testLocksUserOnInvalidTwoFactorCode(int $initialAttempts, int $lockedSeconds): void
    {
        $user = $this->createPartialMock(FrontendUser::class, ['save']);
        $user->username = 'foo';
        $user->loginAttempts = $initialAttempts;
        $user->locked = 0;

        $user
            ->expects($this->once())
            ->method('save')
        ;

        $token = $this->createMock(TwoFactorTokenInterface::class);
        $token
            ->expects($this->once())
            ->method('getUser')
            ->willReturn($user)
        ;

        $authProvider = $this->createMock(AuthenticationProviderInterface::class);
        $authProvider
            ->expects($this->never())
            ->method('authenticate')
        ;

        $userChecker = $this->createMock(UserCheckerInterface::class);
        $userChecker
            ->expects($this->once())
            ->method('checkPreAuth')
            ->with($user)
            ->willThrowException(new InvalidTwoFactorCodeException())
        ;

        $userChecker
            ->expects($this->never())
            ->method('checkPostAuth')
        ;

        $provider = $this->createTwoFactorProvider($authProvider, $userChecker);
        $hasException = false;

        ClockMock::withClockMock(true);

        try {
            $provider->authenticate($token);
        } catch (\Exception $e) {
            if ($lockedSeconds > 0) {
                /** @var LockedException $e */
                $this->assertInstanceOf(LockedException::class, $e);
                $this->assertSame($user, $e->getUser());
            } else {
                $this->assertInstanceOf(InvalidTwoFactorCodeException::class, $e);
            }

            $hasException = true;
        }

        $this->assertTrue($hasException);
        $this->assertSame($initialAttempts + 1, $user->loginAttempts);

        if ($lockedSeconds > 0) {
            $this->assertSame(time() + $lockedSeconds, $user->locked);
        } else {
            $this->assertSame(0, $user->locked);
        }

        ClockMock::withClockMock(false);
    }

    public function invalidTwoFactorCodeProvider(): \Generator
    {
        yield [0, 0];
        yield [1, 0];

        // Locks on the third invalid attempt
        yield [2, 60];
        yield [3, 120];
        yield [4, 180];
        yield [5, 240];
        yield [6, 300];
        yield [7, 360];
        yield [8, 420];
        yield [9, 480];
    }

    public function testIgnoresAnyExceptionButInvalidTwoFactor(): void
    {
        $user = $this->createPartialMock(FrontendUser::class, ['save']);
        $user->username = 'foo';
        $user->loginAttempts = 0;
        $user->locked = 0;

        $user
            ->expects($this->never())
            ->method('save')
        ;

        $token = $this->createMock(TwoFactorTokenInterface::class);
        $token
            ->expects($this->once())
            ->method('getUser')
            ->willReturn($user)
        ;

        $authProvider = $this->createMock(AuthenticationProviderInterface::class);
        $authProvider
            ->expects($this->never())
            ->method('authenticate')
        ;

        $exception = new \RuntimeException();

        $userChecker = $this->createMock(UserCheckerInterface::class);
        $userChecker
            ->expects($this->once())
            ->method('checkPreAuth')
            ->with($user)
            ->willThrowException($exception)
        ;

        $userChecker
            ->expects($this->never())
            ->method('checkPostAuth')
        ;

        $provider = $this->createTwoFactorProvider($authProvider, $userChecker);
        $hasException = false;

        try {
            $provider->authenticate($token);
        } catch (\Exception $e) {
            $this->assertSame($exception, $e);
            $hasException = true;
        }

        $this->assertTrue($hasException);
        $this->assertSame(0, $user->loginAttempts);
    }

    public function testOnlyCallsTwoFactorAuthenticatorWithoutContaoUser(): void
    {
        $token = $this->createMock(TwoFactorTokenInterface::class);
        $token
            ->expects($this->once())
            ->method('getUser')
            ->willReturn($this->createMock(UserInterface::class))
        ;

        $authProvider = $this->createMock(AuthenticationProviderInterface::class);
        $authProvider
            ->expects($this->once())
            ->method('authenticate')
            ->with($token)
            ->willReturn($token)
        ;

        $userChecker = $this->createMock(UserCheckerInterface::class);
        $userChecker
            ->expects($this->never())
            ->method('checkPreAuth')
        ;

        $userChecker
            ->expects($this->never())
            ->method('checkPostAuth')
        ;

        $provider = $this->createTwoFactorProvider($authProvider, $userChecker);
        $provider->authenticate($token);
    }

    public function testHandlesContaoUsers(): void
    {
        $user = $this->createPartialMock(FrontendUser::class, ['getPassword', 'save']);
        $user->username = 'foo';
        $user->loginAttempts = 0;

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

        $provider = $this->createUsernamePasswordProvider();

        $this->expectException(BadCredentialsException::class);
        $this->expectExceptionMessage('Invalid password submitted for username "foo"');

        $provider->checkAuthentication($user, $token);
    }

    public function testBeginsTwoFactorAuthenticationForContaoUsers(): void
    {
        $user = $this->createPartialMock(BackendUser::class, ['save', 'getUserIdentifier']);
        $user->admin = '1';

        $token = new UsernamePasswordToken($user, 'contao_frontend');

        $twoFactorHandler = $this->createMock(AuthenticationHandlerInterface::class);
        $twoFactorHandler
            ->expects($this->once())
            ->method('beginTwoFactorAuthentication')
            ->willReturn($token)
        ;

        $trustedDeviceManager = $this->createMock(TrustedDeviceManagerInterface::class);
        $trustedDeviceManager
            ->expects($this->once())
            ->method('isTrustedDevice')
            ->with($user, 'contao_frontend')
            ->willReturn(false)
        ;

        $provider = $this->createUsernamePasswordProvider(null, $twoFactorHandler, $trustedDeviceManager);
        $provider->authenticate($token);
    }

    public function testSkipsTwoFactorAuthenticationForTrustedDevices(): void
    {
        $user = $this->createPartialMock(BackendUser::class, ['save', 'getUserIdentifier']);
        $user->admin = '1';

        $token = new UsernamePasswordToken($user, 'contao_frontend');

        $twoFactorHandler = $this->createMock(AuthenticationHandlerInterface::class);
        $twoFactorHandler
            ->expects($this->never())
            ->method('beginTwoFactorAuthentication')
        ;

        $trustedDeviceManager = $this->createMock(TrustedDeviceManagerInterface::class);
        $trustedDeviceManager
            ->expects($this->once())
            ->method('isTrustedDevice')
            ->with($user, 'contao_frontend')
            ->willReturn(true)
        ;

        $provider = $this->createUsernamePasswordProvider(null, $twoFactorHandler, $trustedDeviceManager);
        $provider->authenticate($token);
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

        $provider = $this->createUsernamePasswordProvider();
        $provider->checkAuthentication($user, $token);

        $this->addToAssertionCount(1); // does not throw an exception
    }

    public function testLocksAUserAfterAFailedLoginAttempt(): void
    {
        $user = $this->createPartialMock(FrontendUser::class, ['getPassword', 'save']);
        $user->username = 'foo';
        $user->locked = 0;
        $user->loginAttempts = 3;
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

        $provider = $this->createUsernamePasswordProvider($framework);

        $this->expectException(LockedException::class);
        $this->expectExceptionMessage('User "foo" has been locked for 120 seconds');

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

        $provider = $this->createUsernamePasswordProvider();

        $this->expectException(AuthenticationException::class);
        $this->expectExceptionMessage('Unsupported user');

        $provider->checkAuthentication($this->createMock(FrontendUser::class), $token);
    }

    /**
     * @group legacy
     * @dataProvider getCheckCredentialsHookData
     */
    public function testTriggersTheCheckCredentialsHook(string $callback): void
    {
        $this->expectDeprecation('Since contao/core-bundle 4.5: Using the "checkCredentials" hook has been deprecated %s.');

        $user = $this->createPartialMock(FrontendUser::class, ['getPassword', 'save']);
        $user->username = 'foo';
        $user->loginAttempts = 0;

        $user
            ->expects($this->once())
            ->method('getPassword')
            ->willReturn('foobar')
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
            ->method('getUserIdentifier')
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

        $provider = $this->createUsernamePasswordProvider($framework);

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

    public function onValidCredentials(string $username): bool
    {
        return true;
    }

    public function onInvalidCredentials(string $username): bool
    {
        return false;
    }

    private function createUsernamePasswordProvider(ContaoFramework $framework = null, AuthenticationHandlerInterface $twoFactorHandler = null, TrustedDeviceManagerInterface $trustedDeviceManager = null): AuthenticationProvider
    {
        $userProvider = $this->createMock(UserProviderInterface::class);
        $userChecker = $this->createMock(UserCheckerInterface::class);
        $providerKey = 'contao_frontend';
        $passwordHasherFactory = $this->createMock(PasswordHasherFactoryInterface::class);
        $framework ??= $this->createMock(ContaoFramework::class);
        $twoFactorHandler ??= $this->createMock(AuthenticationHandlerInterface::class);
        $trustedDeviceManager ??= $this->createMock(TrustedDeviceManagerInterface::class);

        $contextFactory = $this->createMock(AuthenticationContextFactoryInterface::class);
        $contextFactory
            ->method('create')
            ->willReturnCallback(
                static fn ($request, $token, $firewallName) => new AuthenticationContext($request, $token, $firewallName)
            )
        ;

        $requestStack = $this->createMock(RequestStack::class);
        $requestStack
            ->method('getMainRequest')
            ->willReturn($this->createMock(Request::class))
        ;

        return new AuthenticationProvider(
            $userProvider,
            $userChecker,
            $providerKey,
            $passwordHasherFactory,
            $framework,
            $this->createMock(AuthenticationProviderInterface::class),
            $twoFactorHandler,
            $contextFactory,
            $requestStack,
            $trustedDeviceManager
        );
    }

    private function createTwoFactorProvider(AuthenticationProviderInterface $twoFactorAuthenticationProvider = null, UserCheckerInterface $userChecker = null): AuthenticationProvider
    {
        $userProvider = $this->createMock(UserProviderInterface::class);
        $providerKey = 'contao_frontend';
        $passwordHasherFactory = $this->createMock(PasswordHasherFactoryInterface::class);
        $framework = $this->createMock(ContaoFramework::class);
        $twoFactorAuthenticationProvider ??= $this->createMock(AuthenticationProviderInterface::class);
        $userChecker ??= $this->createMock(UserCheckerInterface::class);

        $requestStack = $this->createMock(RequestStack::class);
        $requestStack
            ->method('getMainRequest')
            ->willReturn($this->createMock(Request::class))
        ;

        $contextFactory = $this->createMock(AuthenticationContextFactoryInterface::class);
        $contextFactory
            ->method('create')
            ->willReturnCallback(
                static fn ($request, $token, $firewallName) => new AuthenticationContext($request, $token, $firewallName)
            )
        ;

        return new AuthenticationProvider(
            $userProvider,
            $userChecker,
            $providerKey,
            $passwordHasherFactory,
            $framework,
            $twoFactorAuthenticationProvider,
            $this->createMock(AuthenticationHandlerInterface::class),
            $contextFactory,
            $requestStack,
            $this->createMock(TrustedDeviceManagerInterface::class)
        );
    }
}
