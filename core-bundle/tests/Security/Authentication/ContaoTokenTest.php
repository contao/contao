<?php

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Security\Authentication;

use Contao\BackendUser;
use Contao\CoreBundle\Security\Authentication\ContaoToken;
use Contao\CoreBundle\Tests\TestCase;
use Contao\FrontendUser;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\Role\Role;
use Symfony\Component\Security\Core\Role\RoleInterface;

/**
 * Tests the ContaoToken class.
 *
 * @author Leo Feyer <https://github.com/leofeyer>
 */
class ContaoTokenTest extends TestCase
{
    /**
     * Tests a front end user.
     */
    public function testHandlesFrontEndUsers()
    {
        $user = $this->createMock(FrontendUser::class);

        $user
            ->method('authenticate')
            ->willReturn(true)
        ;

        $token = new ContaoToken($user);

        $this->assertTrue($token->isAuthenticated());
        $this->assertSame('', $token->getCredentials());

        /** @var RoleInterface[] $roles */
        $roles = $token->getRoles();

        $this->assertCount(1, $roles);
        $this->assertSame((new Role('ROLE_MEMBER'))->getRole(), $roles[0]->getRole());
    }

    /**
     * Tests a back end user.
     */
    public function testHandlesBackEndUsers()
    {
        $user = $this->createMock(BackendUser::class);

        $user
            ->method('__get')
            ->with('isAdmin')
            ->willReturn(true)
        ;

        $user
            ->method('authenticate')
            ->willReturn(true)
        ;

        $token = new ContaoToken($user);

        $this->assertTrue($token->isAuthenticated());
        $this->assertSame('', $token->getCredentials());

        /** @var RoleInterface[] $roles */
        $roles = $token->getRoles();

        $this->assertCount(2, $roles);
        $this->assertSame((new Role('ROLE_USER'))->getRole(), $roles[0]->getRole());
        $this->assertSame((new Role('ROLE_ADMIN'))->getRole(), $roles[1]->getRole());
    }

    /**
     * Tests an unauthenticated user.
     */
    public function testFailsIfTheUserIsNotAuthenticated()
    {
        $user = $this->createMock(FrontendUser::class);

        $user
            ->method('authenticate')
            ->willReturn(false)
        ;

        $this->expectException(UsernameNotFoundException::class);

        new ContaoToken($user);
    }
}
