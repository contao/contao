<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2017 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Test\Security\Authentication\Provider;

use Contao\Config;
use Contao\CoreBundle\Framework\ContaoFrameworkInterface;
use Contao\CoreBundle\Security\Authentication\Provider\AuthenticationProvider;
use Contao\CoreBundle\Security\Exception\LockedException;
use Contao\CoreBundle\Tests\TestCase;
use Contao\FrontendUser;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Encoder\EncoderFactoryInterface;
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
        $this->expectExceptionMessage('The credentials were changed from another session.');

        $provider->checkAuthentication($user, $token);
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

    /**
     * @group legacy
     *
     * @expectedDeprecation Using the checkCredentials hook has been deprecated %s.
     */
    public function testTriggersTheCheckCredentialsHook(): void
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

        $framework = $this->mockContaoFramework();

        $framework
            ->expects($this->atLeastOnce())
            ->method('initialize')
        ;

        $framework
            ->expects($this->once())
            ->method('createInstance')
            ->with(__CLASS__)
            ->willReturn($this)
        ;

        $GLOBALS['TL_HOOKS']['checkCredentials'] = [
            [__CLASS__, 'onCheckCredentialsTrue'],
            [__CLASS__, 'onCheckCredentialsFalse'],
        ];

        $provider = $this->mockProvider($framework);
        $provider->checkAuthentication($user, $token);

        unset($GLOBALS['TL_HOOKS']);
    }

    /**
     * @param string        $username
     * @param string        $password
     * @param UserInterface $user
     *
     * @return bool
     */
    public function onCheckCredentialsTrue(string $username, string $password, UserInterface $user): bool
    {
        $this->assertSame('foo', $username);
        $this->assertSame('bar', $password);
        $this->assertInstanceOf('Contao\FrontendUser', $user);

        return true;
    }

    /**
     * @param string        $username
     * @param string        $password
     * @param UserInterface $user
     *
     * @return bool
     */
    public function onCheckCredentialsFalse(string $username, string $password, UserInterface $user): bool
    {
        $this->assertSame('foo', $username);
        $this->assertSame('bar', $password);
        $this->assertInstanceOf('Contao\FrontendUser', $user);

        return false;
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
