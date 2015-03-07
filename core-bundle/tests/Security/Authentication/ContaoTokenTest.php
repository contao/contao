<?php

/**
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2015 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Test\Security\Authentication;

use Contao\BackendUser;
use Contao\FrontendUser;
use Contao\InvalidUser;
use Contao\CoreBundle\Security\Authentication\ContaoToken;
use Contao\CoreBundle\Test\TestCase;

/**
 * Tests the ContaoToken class.
 *
 * @author Leo Feyer <https://github.com/leofeyer>
 */
class ContaoTokenTest extends TestCase
{
    /**
     * Tests the object instantiation.
     *
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testInstantiation()
    {
        $token = new ContaoToken(FrontendUser::getInstance());

        $this->assertInstanceOf('Contao\CoreBundle\Security\Authentication\ContaoToken', $token);
    }

    /**
     * Tests the object instantiation.
     *
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testAuthentication()
    {
        $token = new ContaoToken(BackendUser::getInstance());

        $this->assertTrue($token->isAuthenticated());
        $this->assertEquals('', $token->getCredentials());
    }

    /**
     * Tests an unauthenticated user.
     *
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     * @expectedException \Symfony\Component\Security\Core\Exception\UsernameNotFoundException
     */
    public function testInvalidUser()
    {
        /** @var FrontendUser|object $user */
        $user = FrontendUser::getInstance();
        $user->authenticated = false;

        new ContaoToken($user);
    }
}
