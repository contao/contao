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

class FrontendPreviewTokenTest extends TestCase
{
    public function testAuthenticatesUsers(): void
    {
        $user = $this->createMock(FrontendUser::class);
        $user
            ->expects($this->once())
            ->method('getRoles')
            ->willReturn(['ROLE_MEMBER'])
        ;

        $token = new FrontendPreviewToken($user, false);

        $this->assertTrue($token->isAuthenticated());
        $this->assertSame($user, $token->getUser());

        $this->assertSame(['ROLE_MEMBER'], $token->getRoleNames());
    }

    public function testAuthenticatesGuests(): void
    {
        $token = new FrontendPreviewToken(null, false);

        $this->assertTrue($token->isAuthenticated());
        $this->assertSame('anon.', $token->getUser()); // @phpstan-ignore-line
    }

    public function testReturnsThePublicationStatus(): void
    {
        $token = new FrontendPreviewToken(null, true);

        $this->assertTrue($token->showUnpublished());
    }

    public function testSerializesItself(): void
    {
        $token = new FrontendPreviewToken(null, true);
        $serialized = $token->__serialize();
        $expected = [true, [$token->getUser(), true, null, [], []], null];

        $this->assertSame($expected, $serialized);

        $token = new FrontendPreviewToken(null, false);

        $this->assertFalse($token->showUnpublished());

        $token->__unserialize($expected);

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
}
