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
use Contao\CoreBundle\Security\Authentication\ContaoToken;
use Contao\CoreBundle\Test\TestCase;
use Symfony\Component\Security\Core\Role\Role;

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

        $this->assertInstanceOf('Contao\\CoreBundle\\Security\\Authentication\\ContaoToken', $token);
    }

    /**
     * Tests a front end user.
     *
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testFrontendUser()
    {
        $token = new ContaoToken(FrontendUser::getInstance());

        $this->assertTrue($token->isAuthenticated());
        $this->assertEquals('', $token->getCredentials());

        $this->assertEquals(
            [
                new Role('ROLE_MEMBER'),
            ],
            $token->getRoles()
        );
    }

    /**
     * Tests a back end user.
     *
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testBackendUser()
    {
        $token = new ContaoToken(BackendUser::getInstance());

        $this->assertTrue($token->isAuthenticated());
        $this->assertEquals('', $token->getCredentials());

        $this->assertEquals(
            [
                new Role('ROLE_USER'),
                new Role('ROLE_ADMIN'),
            ],
            $token->getRoles()
        );
    }

    /**
     * Tests an unauthenticated user.
     *
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     * @expectedException \Symfony\Component\Security\Core\Exception\UsernameNotFoundException
     */
    public function testUnauthenticatedUser()
    {
        /** @var FrontendUser|object $user */
        $user = FrontendUser::getInstance();
        $user->authenticated = false;

        new ContaoToken($user);
    }
}
