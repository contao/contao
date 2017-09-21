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

use Contao\CoreBundle\ContaoCoreBundle;
use Contao\CoreBundle\Security\Authentication\ContaoToken;
use Contao\CoreBundle\Security\ContaoAuthenticator;
use Contao\CoreBundle\Tests\TestCase;
use Contao\User;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\AnonymousToken;
use Symfony\Component\Security\Core\Authentication\Token\PreAuthenticatedToken;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\User\UserProviderInterface;

class ContaoAuthenticatorTest extends TestCase
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
        $authenticator = new ContaoAuthenticator($this->mockScopeMatcher());
        $authenticator->setContainer($this->mockContainerWithContaoScopes(ContaoCoreBundle::SCOPE_FRONTEND));

        $provider = $this->mockUserProvider();

        $this->assertInstanceOf(
            'Contao\CoreBundle\Security\Authentication\ContaoToken',
            $authenticator->authenticateToken(new ContaoToken($this->mockUser()), $provider, 'frontend')
        );

        $this->assertInstanceOf(
            'Contao\CoreBundle\Security\Authentication\ContaoToken',
            $authenticator->authenticateToken(new AnonymousToken('frontend', 'anon.'), $provider, 'frontend')
        );

        $token = new AnonymousToken('console', 'anon.');

        $this->assertSame($token, $authenticator->authenticateToken($token, $provider, 'console'));
    }

    public function testFailsToAuthenticateAnInvalidToken(): void
    {
        $authenticator = new ContaoAuthenticator($this->mockScopeMatcher());
        $authenticator->setContainer($this->mockContainerWithContaoScopes(ContaoCoreBundle::SCOPE_FRONTEND));

        $this->expectException(AuthenticationException::class);

        $authenticator->authenticateToken(
            new PreAuthenticatedToken('foo', 'bar', 'console'), $this->mockUserProvider(), 'console'
        );
    }

    public function testFailsToAuthenticateATokenIfThereIsNoContainerContainer(): void
    {
        $authenticator = new ContaoAuthenticator($this->mockScopeMatcher());

        $this->expectException('LogicException');

        $authenticator->authenticateToken(
            new AnonymousToken('frontend', 'anon.'), $this->mockUserProvider(), 'frontend'
        );
    }

    public function testChecksIfATokenIsSupported(): void
    {
        $authenticator = new ContaoAuthenticator($this->mockScopeMatcher());

        $this->assertTrue($authenticator->supportsToken(new ContaoToken($this->mockUser()), 'frontend'));
        $this->assertTrue($authenticator->supportsToken(new AnonymousToken('anon.', 'foo'), 'frontend'));

        $this->assertFalse(
            $authenticator->supportsToken(new PreAuthenticatedToken('foo', 'bar', 'console'), 'console')
        );
    }

    /**
     * Mocks a user object.
     *
     * @return User|\PHPUnit_Framework_MockObject_MockObject
     */
    private function mockUser(): User
    {
        $user = $this
            ->getMockBuilder(User::class)
            ->disableOriginalConstructor()
            ->setMethods(['authenticate'])
            ->getMock()
        ;

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

                    throw new UsernameNotFoundException();
                }
            )
        ;

        return $provider;
    }
}
