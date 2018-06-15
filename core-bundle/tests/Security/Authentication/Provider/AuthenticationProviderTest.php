<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Test\Security\Authentication\Provider;

use Contao\Config;
use Contao\Controller;
use Contao\CoreBundle\Framework\ContaoFrameworkInterface;
use Contao\CoreBundle\Security\Authentication\Provider\AuthenticationProvider;
use Contao\CoreBundle\Security\Exception\LockedException;
use Contao\CoreBundle\Tests\TestCase;
use Contao\FrontendUser;
use Contao\System;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Encoder\EncoderFactoryInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Translation\TranslatorInterface;

class AuthenticationProviderTest extends TestCase
{
    public function testCanBeInstantiated(): void
    {
        $provider = $this->mockProvider();

        $this->assertInstanceOf('Contao\CoreBundle\Security\Authentication\Provider\AuthenticationProvider', $provider);
    }

    public function testHandlesContaoUsers(): void
    {
        /** @var FrontendUser|\PHPUnit_Framework_MockObject_MockObject $user */
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

        $provider = $this->mockProvider();

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

        $provider = $this->mockProvider();
        $provider->checkAuthentication($user, $token);

        $this->addToAssertionCount(1); // does not throw an exception
    }

    public function testLocksAUserAfterThreeFailedLoginAttempts(): void
    {
        /** @var FrontendUser|\PHPUnit_Framework_MockObject_MockObject $user */
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

        $adapter = $this->mockAdapter(['get']);

        $adapter
            ->expects($this->once())
            ->method('get')
            ->with('adminEmail')
            ->willReturn('admin@example.com')
        ;

        $framework = $this->mockContaoFramework([Config::class => $adapter]);

        $framework
            ->expects($this->atLeastOnce())
            ->method('initialize')
        ;

        $translator = $this->createMock(TranslatorInterface::class);

        $translator
            ->method('trans')
            ->willReturnCallback(
                function (string $key, array $args): ?string {
                    if ('MSC.lockedAccount.0' === $key) {
                        return 'Account locked';
                    }

                    if ('MSC.lockedAccount.1' === $key) {
                        $this->assertSame('foo', $args[0]);
                        $this->assertSame('Foo Bar', $args[1]);
                        $this->assertSame(5, $args[3]);

                        return 'The account has been locked';
                    }

                    return null;
                }
            )
        ;

        $requestStack = new RequestStack();
        $requestStack->push(new Request());

        $mailer = $this->createMock(\Swift_Mailer::class);

        $mailer
            ->expects($this->once())
            ->method('send')
        ;

        $provider = $this->mockProvider($framework, $translator, $requestStack, $mailer);

        $this->expectException(LockedException::class);
        $this->expectExceptionMessage('User "foo" has been locked for 5 minutes');

        $provider->checkAuthentication($user, $token);
    }

    public function testFailsToSendTheLockedEmailIfThereIsNoRequest(): void
    {
        /** @var FrontendUser|\PHPUnit_Framework_MockObject_MockObject $user */
        $user = $this->createPartialMock(FrontendUser::class, ['getPassword', 'save']);
        $user->username = 'foo';
        $user->locked = 0;
        $user->loginCount = 1;

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

        $adapter = $this->mockAdapter(['get']);

        $adapter
            ->expects($this->once())
            ->method('get')
            ->with('adminEmail')
            ->willReturn('admin@example.com')
        ;

        $framework = $this->mockContaoFramework([Config::class => $adapter]);

        $framework
            ->expects($this->atLeastOnce())
            ->method('initialize')
        ;

        $provider = $this->mockProvider($framework);

        $this->expectException('RuntimeException');
        $this->expectExceptionMessage('The request stack did not contain a request');

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

        $provider = $this->mockProvider();

        $this->expectException(AuthenticationException::class);
        $this->expectExceptionMessage('Unsupported user');

        $provider->checkAuthentication($this->createMock(FrontendUser::class), $token);
    }

    /**
     * @param bool $success
     *
     * @group legacy
     * @dataProvider getCheckCredentialsHookData
     *
     * @expectedDeprecation Using the checkCredentials hook has been deprecated %s.
     */
    public function testTriggersTheCheckCredentialsHook(bool $success): void
    {
        /** @var FrontendUser|\PHPUnit_Framework_MockObject_MockObject $user */
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

        $listener = $this->createPartialMock(Controller::class, ['onCheckCredentials']);

        $listener
            ->expects($this->once())
            ->method('onCheckCredentials')
            ->with('foo', 'bar', $user)
            ->willReturn($success)
        ;

        $systemAdapter = $this->mockAdapter(['importStatic']);

        $systemAdapter
            ->expects($this->once())
            ->method('importStatic')
            ->with('HookListener')
            ->willReturn($listener)
        ;

        $framework = $this->mockContaoFramework([System::class => $systemAdapter]);

        $framework
            ->expects($this->atLeastOnce())
            ->method('initialize')
        ;

        $GLOBALS['TL_HOOKS']['checkCredentials'] = [['HookListener', 'onCheckCredentials']];

        $provider = $this->mockProvider($framework);

        if (!$success) {
            $this->expectException(BadCredentialsException::class);
            $this->expectExceptionMessage('Invalid password submitted for username "foo"');
        }

        $provider->checkAuthentication($user, $token);

        unset($GLOBALS['TL_HOOKS']);
    }

    /**
     * @return array
     */
    public function getCheckCredentialsHookData(): array
    {
        return [
            [true],
            [false],
        ];
    }

    /**
     * Mocks an authentication provider.
     *
     * @param ContaoFrameworkInterface|null $framework
     * @param TranslatorInterface|null      $translator
     * @param RequestStack|null             $requestStack
     * @param \Swift_Mailer|null            $mailer
     *
     * @return AuthenticationProvider
     */
    private function mockProvider(ContaoFrameworkInterface $framework = null, TranslatorInterface $translator = null, RequestStack $requestStack = null, \Swift_Mailer $mailer = null): AuthenticationProvider
    {
        $userProvider = $this->createMock(UserProviderInterface::class);
        $userChecker = $this->createMock(UserCheckerInterface::class);
        $providerKey = 'contao_frontend';
        $encoderFactory = $this->createMock(EncoderFactoryInterface::class);

        if (null === $framework) {
            $framework = $this->createMock(ContaoFrameworkInterface::class);
        }

        if (null === $translator) {
            $translator = $this->createMock(TranslatorInterface::class);
        }

        if (null === $requestStack) {
            $requestStack = new RequestStack();
        }

        if (null === $mailer) {
            $mailer = $this->createMock(\Swift_Mailer::class);
        }

        return new AuthenticationProvider(
            $userProvider,
            $userChecker,
            $providerKey,
            $encoderFactory,
            $framework,
            $translator,
            $requestStack,
            $mailer
        );
    }
}
