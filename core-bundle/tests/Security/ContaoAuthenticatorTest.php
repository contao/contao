<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2017 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Tests\Security;

use Contao\CoreBundle\Security\Authentication\ContaoToken;
use Contao\CoreBundle\Security\ContaoAuthenticator;
use Contao\User;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\AnonymousToken;
use Symfony\Component\Security\Core\Authentication\Token\PreAuthenticatedToken;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\User\UserProviderInterface;

class ContaoAuthenticatorTest extends SecurityTestCase
{
    public function testCanBeInstantiated(): void
    {
        $authenticator = new ContaoAuthenticator($this->mockScopeMatcher());

        $this->assertInstanceOf('Contao\CoreBundle\Security\ContaoAuthenticator', $authenticator);
    }

    public function testCreatesTheToken(): void
    {
        $authenticator = new ContaoAuthenticator($this->mockScopeMatcher());
        $token = $authenticator->createToken(new Request(), 'frontend');

        $this->assertInstanceOf('Symfony\Component\Security\Core\Authentication\Token\AnonymousToken', $token);
        $this->assertSame('frontend', $token->getSecret());
        $this->assertSame('anon.', $token->getUsername());
    }

    public function testAuthenticatesTheToken(): void
    {
        $provider = $this->mockUserProvider();

        $authenticator = new ContaoAuthenticator($this->mockScopeMatcher());
        $authenticator->setContainer($this->mockContainerWithScope('frontend'));

        $token = $authenticator->authenticateToken(new ContaoToken($this->mockUser()), $provider, 'frontend');

        $this->assertInstanceOf('Contao\CoreBundle\Security\Authentication\ContaoToken', $token);

        $token = $authenticator->authenticateToken(new AnonymousToken('frontend', 'anon.'), $provider, 'frontend');

        $this->assertInstanceOf('Contao\CoreBundle\Security\Authentication\ContaoToken', $token);

        $token = new AnonymousToken('console', 'anon.');

        $this->assertSame($token, $authenticator->authenticateToken($token, $provider, 'console'));
    }

    public function testFailsToAuthenticateAnInvalidToken(): void
    {
        $authenticator = new ContaoAuthenticator($this->mockScopeMatcher());
        $authenticator->setContainer($this->mockContainerWithScope('frontend'));

        $token = new PreAuthenticatedToken('foo', 'bar', 'console');

        $this->expectException(AuthenticationException::class);

        $authenticator->authenticateToken($token, $this->mockUserProvider(), 'console');
    }

    public function testFailsToAuthenticateATokenIfThereIsNoContainerContainer(): void
    {
        $authenticator = new ContaoAuthenticator($this->mockScopeMatcher());
        $token = new AnonymousToken('frontend', 'anon.');

        $this->expectException('LogicException');

        $authenticator->authenticateToken($token, $this->mockUserProvider(), 'frontend');
    }

    public function testChecksIfATokenIsSupported(): void
    {
        $authenticator = new ContaoAuthenticator($this->mockScopeMatcher());
        $token = new ContaoToken($this->mockUser());

        $this->assertTrue($authenticator->supportsToken($token, 'frontend'));

        $token = new AnonymousToken('anon.', 'foo');

        $this->assertTrue($authenticator->supportsToken($token, 'frontend'));

        $token = new PreAuthenticatedToken('foo', 'bar', 'console');

        $this->assertFalse($authenticator->supportsToken($token, 'console'));
    }

    /**
     * Mocks a user object.
     *
     * @return User|\PHPUnit_Framework_MockObject_MockObject
     */
    private function mockUser(): User
    {
        $user = $this->createPartialMock(User::class, ['authenticate']);

        $user
            ->method('authenticate')
            ->willReturn(true)
        ;

        return $user;
    }

    /**
     * Mocks a user provider object.
     *
     * @return UserProviderInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private function mockUserProvider(): UserProviderInterface
    {
        $user = $this->mockUser();
        $provider = $this->createMock(UserProviderInterface::class);

        $provider
            ->method('loadUserByUsername')
            ->willReturnCallback(
                function (string $username) use ($user): User {
                    if ('frontend' === $username || 'backend' === $username) {
                        return $user;
                    }

                    throw new UsernameNotFoundException('Can only load user "frontend" or "backend".');
                }
            )
        ;

        return $provider;
    }
}
