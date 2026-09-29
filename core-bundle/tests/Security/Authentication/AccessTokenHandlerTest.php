<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Security\Authentication;

use Contao\BackendUser;
use Contao\CoreBundle\Entity\PersonalAccessToken;
use Contao\CoreBundle\Security\Authentication\AccessTokenHandler;
use Contao\CoreBundle\Security\Authentication\AccessTokenManager;
use Contao\CoreBundle\Security\User\ContaoUserProvider;
use Contao\CoreBundle\Tests\TestCase;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;

class AccessTokenHandlerTest extends TestCase
{
    public function testThrowsBadCredentialsExceptionWithInvalidToken(): void
    {
        $token = 'cto_pat_123_456';

        $accessTokenManager = $this->createMock(AccessTokenManager::class);
        $accessTokenManager
            ->expects($this->once())
            ->method('getValidPersonalAccessToken')
            ->with($token)
            ->willReturn(null)
        ;

        $accessTokenHandler = new AccessTokenHandler($accessTokenManager, $this->createStub(ContaoUserProvider::class));

        $this->expectException(BadCredentialsException::class);

        $accessTokenHandler->getUserBadgeFrom($token);
    }

    public function testAuthenticatesToken(): void
    {
        $token = 'cto_pat_123_456';

        $personalAccessToken = $this->createMock(PersonalAccessToken::class);
        $personalAccessToken
            ->expects($this->once())
            ->method('getUserId')
            ->willReturn(42)
        ;

        $accessTokenManager = $this->createMock(AccessTokenManager::class);
        $accessTokenManager
            ->expects($this->once())
            ->method('getValidPersonalAccessToken')
            ->with($token)
            ->willReturn($personalAccessToken)
        ;

        $user = $this->createMock(BackendUser::class);
        $user
            ->expects($this->once())
            ->method('getUserIdentifier')
            ->willReturn('foobar')
        ;

        $backendUserProvider = $this->createMock(ContaoUserProvider::class);
        $backendUserProvider
            ->expects($this->once())
            ->method('loadUserById')
            ->with(42)
            ->willReturn($user)
        ;

        $accessTokenHandler = new AccessTokenHandler(
            $accessTokenManager,
            $backendUserProvider,
        );

        $badge = $accessTokenHandler->getUserBadgeFrom($token);

        $this->assertSame('foobar', $badge->getUserIdentifier());
    }
}
