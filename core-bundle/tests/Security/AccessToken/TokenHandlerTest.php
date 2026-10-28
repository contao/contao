<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Security\AccessToken;

use Contao\CoreBundle\Entity\PersonalAccessToken;
use Contao\CoreBundle\Security\AccessToken\AccessTokenManager;
use Contao\CoreBundle\Security\AccessToken\TokenHandler;
use Contao\CoreBundle\Security\User\ContaoUserProvider;
use Contao\CoreBundle\Tests\TestCase;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;

class TokenHandlerTest extends TestCase
{
    public function testGetUserBadge(): void
    {
        $accessToken = 'ct_pat_foo_bar';
        $personalAccessToken = new PersonalAccessToken('backend', 42, 'foobar', 'hashed-secret');

        $accessTokenManager = $this->createMock(AccessTokenManager::class);
        $accessTokenManager
            ->expects($this->once())
            ->method('parseAndValidateToken')
            ->with($accessToken)
            ->willReturn($personalAccessToken)
        ;

        $attributes = $this->createMock(ParameterBag::class);
        $attributes
            ->expects($this->once())
            ->method('set')
            ->with('_access_token', $personalAccessToken)
        ;

        $request = new Request();
        $request->attributes = $attributes;

        $requestStack = $this->createMock(RequestStack::class);
        $requestStack
            ->expects($this->once())
            ->method('getCurrentRequest')
            ->willReturn($request)
        ;

        $handler = new TokenHandler(
            $accessTokenManager,
            $this->createStub(ContaoUserProvider::class),
            $requestStack,
            'backend',
        );

        $userBadge = $handler->getUserBadgeFrom($accessToken);

        $this->assertSame(AccessTokenManager::TOKEN_PREFIX.$personalAccessToken->getId()->toBase32(), $userBadge->getUserIdentifier());
    }

    public function testThrowsExceptionIfTokenCannotIsInvalid(): void
    {
        $this->expectException(BadCredentialsException::class);

        $accessToken = 'ct_pat_foo_bar';

        $accessTokenManager = $this->createMock(AccessTokenManager::class);
        $accessTokenManager
            ->expects($this->once())
            ->method('parseAndValidateToken')
            ->with($accessToken)
            ->willReturn(null)
        ;

        $handler = new TokenHandler(
            $accessTokenManager,
            $this->createStub(ContaoUserProvider::class),
            $this->createStub(RequestStack::class),
            'backend',
        );

        $handler->getUserBadgeFrom($accessToken);
    }
}
