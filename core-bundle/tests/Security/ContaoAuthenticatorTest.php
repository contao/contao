<?php

/**
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2015 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Test\Security\Authentication;

use Contao\CoreBundle\Security\Authentication\ContaoToken;
use Contao\CoreBundle\Security\ContaoAuthenticator;
use Contao\CoreBundle\Security\User\ContaoUserProvider;
use Contao\CoreBundle\Test\TestCase;
use Contao\FrontendUser;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\AnonymousToken;
use Symfony\Component\Security\Core\Authentication\Token\PreAuthenticatedToken;

/**
 * Tests the ContaoAuthenticator class.
 *
 * @author Leo Feyer <https://github.com/leofeyer>
 */
class ContaoAuthenticatorTest extends TestCase
{
    /**
     * Tests the object instantiation.
     */
    public function testInstantiation()
    {
        $authenticator = new ContaoAuthenticator(new ContaoUserProvider());

        $this->assertInstanceOf('Contao\CoreBundle\Security\ContaoAuthenticator', $authenticator);
    }

    /**
     * Tests creating an authentication token.
     */
    public function testCreateToken()
    {
        $authenticator = new ContaoAuthenticator(new ContaoUserProvider());
        $token         = $authenticator->createToken(new Request(), 'frontend');

        $this->assertInstanceOf('Symfony\Component\Security\Core\Authentication\Token\AnonymousToken', $token);
        $this->assertEquals('frontend', $token->getKey());
        $this->assertEquals('anon.', $token->getUsername());
    }

    /**
     * Tests authenticating a token.
     */
    public function testAuthenticateToken()
    {
        $authenticator = new ContaoAuthenticator(new ContaoUserProvider());

        $this->assertInstanceOf(
            'Contao\CoreBundle\Security\Authentication\ContaoToken',
            $authenticator->authenticateToken(new ContaoToken(FrontendUser::getInstance()), new ContaoUserProvider(), 'frontend')
        );

        $this->assertInstanceOf(
            'Contao\CoreBundle\Security\Authentication\ContaoToken',
            $authenticator->authenticateToken(new AnonymousToken('frontend', 'anon.'), new ContaoUserProvider(), 'frontend')
        );

        $this->assertEquals(
            new AnonymousToken('console', 'anon.'),
            $authenticator->authenticateToken(new AnonymousToken('console', 'anon.'), new ContaoUserProvider(), 'console')
        );
    }

    /**
     * Tests authenticating an invalid token.
     *
     * @expectedException \Symfony\Component\Security\Core\Exception\AuthenticationException
     */
    public function testAuthenticateInvalidToken()
    {
        $authenticator = new ContaoAuthenticator(new ContaoUserProvider());
        $authenticator->authenticateToken(new PreAuthenticatedToken('foo', 'bar', 'console'), new ContaoUserProvider(), 'console');
    }

    /**
     * Tests the token support.
     */
    public function testSupportsToken()
    {
        $authenticator = new ContaoAuthenticator(new ContaoUserProvider());

        $this->assertTrue($authenticator->supportsToken(new ContaoToken(FrontendUser::getInstance()), 'frontend'));
        $this->assertTrue($authenticator->supportsToken(new AnonymousToken('anon.', 'foo'), 'frontend'));
        $this->assertFalse($authenticator->supportsToken(new PreAuthenticatedToken('foo', 'bar', 'console'), 'console'));
    }
}
