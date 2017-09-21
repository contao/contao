<?php

declare(strict_types=1);

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
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\Role\Role;
use Symfony\Component\Security\Core\Role\RoleInterface;

class ContaoTokenTest extends TestCase
{
    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testCanBeInstantiated(): void
    {
        $token = new ContaoToken(FrontendUser::getInstance());

        $this->assertInstanceOf('Contao\CoreBundle\Security\Authentication\ContaoToken', $token);
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testHandlesFrontEndUsers(): void
    {
        $token = new ContaoToken(FrontendUser::getInstance());

        $this->assertTrue($token->isAuthenticated());
        $this->assertSame('', $token->getCredentials());

        /** @var RoleInterface[] $roles */
        $roles = $token->getRoles();

        $this->assertCount(1, $roles);
        $this->assertSame((new Role('ROLE_MEMBER'))->getRole(), $roles[0]->getRole());
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testHandlesBackEndUsers(): void
    {
        $token = new ContaoToken(BackendUser::getInstance());

        $this->assertTrue($token->isAuthenticated());
        $this->assertSame('', $token->getCredentials());

        /** @var RoleInterface[] $roles */
        $roles = $token->getRoles();

        $this->assertCount(2, $roles);
        $this->assertSame((new Role('ROLE_USER'))->getRole(), $roles[0]->getRole());
        $this->assertSame((new Role('ROLE_ADMIN'))->getRole(), $roles[1]->getRole());
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testFailsIfTheUserIsNotAuthenticated(): void
    {
        /** @var FrontendUser|object $user */
        $user = FrontendUser::getInstance();
        $user->authenticated = false;

        $this->expectException(UsernameNotFoundException::class);

        new ContaoToken($user);
    }
}
