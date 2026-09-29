<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\OAuthBundle\Tests\Security;

use Contao\OAuthBundle\AuthorizationServer\Entity\AccessToken;
use Contao\OAuthBundle\AuthorizationServer\Entity\Client;
use Contao\OAuthBundle\AuthorizationServer\Repository\AccessTokenRepository;
use Contao\OAuthBundle\KeyProvider;
use Contao\OAuthBundle\ResourceContext;
use Contao\OAuthBundle\Security\AccessTokenValidator;
use Contao\OAuthBundle\Security\InvalidAccessTokenException;
use PHPUnit\Framework\TestCase;

final class AccessTokenValidatorTest extends TestCase
{
    public function testValidatesATokenIssuedWithTheSameSecret(): void
    {
        $jwt = $this->getAccessToken('secret', 'https://example.com/contao/mcp');

        $this->assertSame(
            ['user' => 'k.jones', 'client_id' => 'https://claude.ai/oauth/client', 'jti' => 'token-id'],
            $this->mockValidator('secret')->validate($jwt),
        );
    }

    public function testRejectsATokenIssuedWithAnotherSecret(): void
    {
        $jwt = $this->getAccessToken('other-secret', 'https://example.com/contao/mcp');

        $this->expectException(InvalidAccessTokenException::class);
        $this->expectExceptionMessage('The access token is invalid.');

        $this->mockValidator('secret')->validate($jwt);
    }

    public function testRejectsATokenForAnotherResource(): void
    {
        $jwt = $this->getAccessToken('secret', 'https://example.com/api');

        $this->expectException(InvalidAccessTokenException::class);
        $this->expectExceptionMessage('The access token is invalid.');

        $this->mockValidator('secret')->validate($jwt);
    }

    public function testRejectsARevokedToken(): void
    {
        $jwt = $this->getAccessToken('secret', 'https://example.com/contao/mcp');

        $this->expectException(InvalidAccessTokenException::class);
        $this->expectExceptionMessage('The access token has been revoked.');

        $this->mockValidator('secret', true)->validate($jwt);
    }

    public function testRejectsAMalformedToken(): void
    {
        $this->expectException(InvalidAccessTokenException::class);
        $this->expectExceptionMessage('Malformed access token.');

        $this->mockValidator('secret')->validate('foo.bar');
    }

    private function getAccessToken(string $secret, string $resource): string
    {
        $token = new AccessToken('https://example.com', $resource);
        $token->setPrivateKey(new KeyProvider($secret)->getSigningKey());
        $token->setIdentifier('token-id');
        $token->setClient(new Client('https://claude.ai/oauth/client', 'Claude', []));
        $token->setUserIdentifier('k.jones');
        $token->setExpiryDateTime(new \DateTimeImmutable('+1 hour'));

        return $token->toString();
    }

    private function mockValidator(string $secret, bool $revoked = false): AccessTokenValidator
    {
        $context = $this->createStub(ResourceContext::class);
        $context
            ->method('getIssuer')
            ->willReturn('https://example.com')
        ;

        $context
            ->method('getResource')
            ->willReturn('https://example.com/contao/mcp')
        ;

        $accessTokens = $this->createStub(AccessTokenRepository::class);
        $accessTokens
            ->method('isAccessTokenRevoked')
            ->willReturn($revoked)
        ;

        return new AccessTokenValidator(new KeyProvider($secret), $context, $accessTokens);
    }
}
