<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2017 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Tests\Security\Authentication;

use Contao\BackendUser;
use Contao\CoreBundle\Security\Authentication\ContaoToken;
use Contao\FrontendUser;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\Role\Role;

/**
 * Tests the ContaoToken class.
 *
 * @author Leo Feyer <https://github.com/leofeyer>
 */
class ContaoTokenTest extends \PHPUnit_Framework_TestCase
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
     * Tests a front end user.
     *
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testFrontendUser()
    {
        $token = new ContaoToken(FrontendUser::getInstance());

        $this->assertTrue($token->isAuthenticated());
        $this->assertSame('', $token->getCredentials());

        $roles = $token->getRoles();

        $this->assertCount(1, $roles);
        $this->assertSame((new Role('ROLE_MEMBER'))->getRole(), $roles[0]->getRole());
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
        $this->assertSame('', $token->getCredentials());
        $roles = $token->getRoles();

        $this->assertCount(2, $roles);
        $this->assertSame((new Role('ROLE_USER'))->getRole(), $roles[0]->getRole());
        $this->assertSame((new Role('ROLE_ADMIN'))->getRole(), $roles[1]->getRole());
    }

    /**
     * Tests an unauthenticated user.
     *
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testUnauthenticatedUser()
    {
        /** @var FrontendUser|object $user */
        $user = FrontendUser::getInstance();
        $user->authenticated = false;

        $this->setExpectedException(UsernameNotFoundException::class);

        new ContaoToken($user);
    }
}
