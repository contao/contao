<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2016 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Test\Security;

use Contao\CoreBundle\ContaoCoreBundle;
use Contao\CoreBundle\Security\Authentication\ContaoToken;
use Contao\CoreBundle\Security\ContaoAuthenticator;
use Contao\CoreBundle\Test\TestCase;
use Contao\User;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\AnonymousToken;
use Symfony\Component\Security\Core\Authentication\Token\PreAuthenticatedToken;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\User\UserProviderInterface;

/**
 * Tests the ContaoAuthenticator class.
 *
 * @author Leo Feyer <https://github.com/leofeyer>
 * @author Andreas Schempp <https://github.com/aschempp>
 */
class ContaoAuthenticatorTest extends TestCase
{
    /**
     * Tests the object instantiation.
     */
    public function testInstantiation()
    {
        $authenticator = new ContaoAuthenticator();

        $this->assertInstanceOf('Contao\CoreBundle\Security\ContaoAuthenticator', $authenticator);
    }

    /**
     * Tests creating an authentication token.
     */
    public function testCreateToken()
    {
        $authenticator = new ContaoAuthenticator();
        $token = $authenticator->createToken(new Request(), 'frontend');

        $this->assertInstanceOf('Symfony\Component\Security\Core\Authentication\Token\AnonymousToken', $token);
        $this->assertEquals('frontend', $token->getSecret());
        $this->assertEquals('anon.', $token->getUsername());
    }

    /**
     * Tests authenticating a token.
     */
    public function testAuthenticateToken()
    {
        $authenticator = new ContaoAuthenticator();
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

        $this->assertEquals(
            new AnonymousToken('console', 'anon.'),
            $authenticator->authenticateToken(new AnonymousToken('console', 'anon.'), $provider, 'console')
        );
    }

    /**
     * Tests authenticating an invalid token.
     *
     * @expectedException \Symfony\Component\Security\Core\Exception\AuthenticationException
     */
    public function testAuthenticateInvalidToken()
    {
        $authenticator = new ContaoAuthenticator();
        $authenticator->setContainer($this->mockContainerWithContaoScopes(ContaoCoreBundle::SCOPE_FRONTEND));

        $authenticator->authenticateToken(
            new PreAuthenticatedToken('foo', 'bar', 'console'), $this->mockUserProvider(), 'console'
        );
    }

    /**
     * Tests authenticating a token without the container being set.
     *
     * @expectedException \LogicException
     */
    public function testAuthenticateTokenWithoutContainer()
    {
        $authenticator = new ContaoAuthenticator();

        $authenticator->authenticateToken(
            new AnonymousToken('frontend', 'anon.'), $this->mockUserProvider(), 'frontend'
        );
    }

    /**
     * Tests the token support.
     */
    public function testSupportsToken()
    {
        $authenticator = new ContaoAuthenticator();

        $this->assertTrue($authenticator->supportsToken(new ContaoToken($this->mockUser()), 'frontend'));
        $this->assertTrue($authenticator->supportsToken(new AnonymousToken('anon.', 'foo'), 'frontend'));

        $this->assertFalse(
            $authenticator->supportsToken(new PreAuthenticatedToken('foo', 'bar', 'console'), 'console')
        );
    }

    /**
     * Mocks a user provider object.
     *
     * @return UserProviderInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private function mockUserProvider()
    {
        $user = $this->mockUser();

        $provider = $this->getMock(
            'Symfony\Component\Security\Core\User\UserProviderInterface',
            ['loadUserByUsername', 'refreshUser', 'supportsClass']
        );

        $provider
            ->expects($this->any())
            ->method('loadUserByUsername')
            ->willReturnCallback(function ($username) use ($user) {
                if ('frontend' === $username || 'backend' === $username) {
                    return $user;
                } else {
                    throw new UsernameNotFoundException();
                }
            })
        ;

        return $provider;
    }

    /**
     * Mocks a user object.
     *
     * @return User|\PHPUnit_Framework_MockObject_MockObject
     */
    private function mockUser()
    {
        $user = $this->getMock(
            'Contao\User',
            ['authenticate']
        );

        $user
            ->expects($this->any())
            ->method('authenticate')
            ->willReturn(true)
        ;

        return $user;
    }
}
