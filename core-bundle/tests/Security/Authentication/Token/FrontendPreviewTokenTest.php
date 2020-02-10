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
use Symfony\Component\Security\Core\Authentication\Token\AbstractToken;

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

        $roles = $token->getRoles();

        $this->assertIsArray($roles);
        $this->assertCount(1, $roles);
        $this->assertSame('ROLE_MEMBER', $roles[0]->getRole());
    }

    public function testAuthenticatesGuests(): void
    {
        $token = new FrontendPreviewToken(null, false);

        $this->assertTrue($token->isAuthenticated());
        $this->assertSame('anon.', $token->getUser());
    }

    public function testReturnsThePublicationStatus(): void
    {
        $token = new FrontendPreviewToken(null, true);

        $this->assertTrue($token->showUnpublished());
    }

    public function testSerializesItself(): void
    {
        $token = new FrontendPreviewToken(null, true);

        if (method_exists(AbstractToken::class, '__serialize')) {
            $serialized = serialize($token->__serialize());
        } else {
            $serialized = $token->serialize();
        }

        switch (true) {
            case false !== strpos($serialized, '"a:4:{'):
                // Backwards compatility with symfony/security <4.2.3
                $expected = [true, serialize(['anon.', true, [], []])];
                break;

            case false !== strpos($serialized, ';a:4:{'):
                // Backwards compatility with symfony/security <4.3
                $expected = [true, ['anon.', true, [], []]];
                break;

            default:
                $expected = [true, ['anon.', true, [], [], []]];
        }

        $this->assertSame(serialize($expected), $serialized);

        $token = new FrontendPreviewToken(null, false);

        $this->assertFalse($token->showUnpublished());

        if (method_exists(AbstractToken::class, '__unserialize')) {
            $token->__unserialize($expected);
        } else {
            $token->unserialize(serialize($expected));
        }

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
