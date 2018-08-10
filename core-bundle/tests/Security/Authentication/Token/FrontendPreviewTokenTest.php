<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Security\Authentication\Token;

use Contao\CoreBundle\Security\Authentication\Token\FrontendPreviewToken;
use Contao\FrontendUser;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Role\Role;

class FrontendPreviewTokenTest extends TestCase
{
    public function testCanBeInstantiated(): void
    {
        $token = new FrontendPreviewToken(null, false);

        $this->assertInstanceOf('Contao\CoreBundle\Security\Authentication\Token\FrontendPreviewToken', $token);
    }

    public function testAuthenticatesUsers(): void
    {
        $user = $this->createMock(FrontendUser::class);
        $user
            ->expects($this->once())
            ->method('getRoles')
            ->willReturn(['ROLE_USER'])
        ;

        $token = new FrontendPreviewToken($user, false);

        /** @var Role[]|array $roles */
        $roles = $token->getRoles();

        $this->assertTrue($token->isAuthenticated());
        $this->assertSame($user, $token->getUser());
        $this->assertInternalType('array', $roles);
        $this->assertCount(1, $roles);
        $this->assertInstanceOf(Role::class, $roles[0]);
        $this->assertSame('ROLE_USER', $roles[0]->getRole());
    }

    public function testAuthenticatesGuests(): void
    {
        $token = new FrontendPreviewToken(null, false);

        $roles = $token->getRoles();

        $this->assertTrue($token->isAuthenticated());
        $this->assertSame('anon.', $token->getUser());
        $this->assertInternalType('array', $roles);
        $this->assertCount(0, $roles);
    }

    public function testReturnsThePublicationStatus(): void
    {
        $token = new FrontendPreviewToken(null, true);

        $this->assertTrue($token->showUnpublished());
    }

    public function testSerializesItself(): void
    {
        $token = new FrontendPreviewToken(null, true);

        $this->assertSame($this->getSerializedToken(), $token->serialize());
    }

    public function testUnserializesItself(): void
    {
        $token = new FrontendPreviewToken(null, false);

        $this->assertFalse($token->showUnpublished());

        $token->unserialize($this->getSerializedToken());

        $this->assertTrue($token->showUnpublished());
    }

    public function testDoesNotReturnCredentials(): void
    {
        $user = $this->createMock(FrontendUser::class);
        $user
            ->expects($this->once())
            ->method('getRoles')
            ->willReturn(['ROLE_USER'])
        ;

        $token = new FrontendPreviewToken($user, false);

        $this->assertNull($token->getCredentials());
    }

    private function getSerializedToken(): string
    {
        return serialize([true, serialize(['anon.', true, [], []])]);
    }
}
