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

use Contao\CoreBundle\Security\Authentication\FrontendPreviewToken;
use Contao\FrontendUser;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Role\Role;

class FrontendPreviewTokenTest extends TestCase
{
    public function testCanBeInstantiated(): void
    {
        $token = new FrontendPreviewToken(null, false);

        $this->assertInstanceOf('Contao\CoreBundle\Security\Authentication\FrontendPreviewToken', $token);
    }

    public function testAuthenticatesUsers(): void
    {
        $user = $this->createMock(FrontendUser::class);

        $user
            ->expects($this->once())
            ->method('getRoles')
            ->willReturn(['foobar'])
        ;

        $token = new FrontendPreviewToken($user, false);

        /** @var Role[] $roles */
        $roles = $token->getRoles();

        $this->assertTrue($token->isAuthenticated());
        $this->assertSame($user, $token->getUser());
        $this->assertInternalType('array', $roles);
        $this->assertCount(1, $roles);
        $this->assertInstanceOf(Role::class, $roles[0]);
        $this->assertSame('foobar', $roles[0]->getRole());
    }

    public function testAuthenticatesGuests(): void
    {
        $token = new FrontendPreviewToken(null, false);

        /** @var Role[] $roles */
        $roles = $token->getRoles();

        $this->assertTrue($token->isAuthenticated());
        $this->assertSame('anon.', $token->getUser());
        $this->assertInternalType('array', $roles);
        $this->assertCount(0, $roles);
    }

    public function testReturnsTheShowsUnpublishedStatus(): void
    {
        $token = new FrontendPreviewToken(null, true);

        $this->assertTrue($token->showUnpublished());

        $unserialized = unserialize(serialize($token), ['allowed_classes' => true]);

        $this->assertInstanceOf(FrontendPreviewToken::class, $unserialized);
        $this->assertTrue($unserialized->showUnpublished());
    }

    public function testDoesNotReturnCredentials(): void
    {
        $user = $this->createMock(FrontendUser::class);

        $user
            ->expects($this->once())
            ->method('getRoles')
            ->willReturn([])
        ;

        $token = new FrontendPreviewToken($user, false);

        $this->assertNull($token->getCredentials());
    }
}
