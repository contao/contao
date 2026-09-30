<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\OAuthServerBundle\Tests\Security;

use Contao\OAuthServerBundle\ResourceContext;
use Contao\OAuthServerBundle\Security\BearerChallenge;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Response;

final class BearerChallengeTest extends TestCase
{
    public function testSendsTheResourceMetadataAndConfiguredScopes(): void
    {
        $response = new BearerChallenge($this->mockResourceContext())->unauthorized();

        $this->assertSame(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());
        $this->assertSame('{"error":"unauthorized"}', $response->getContent());

        $this->assertSame(
            'Bearer resource_metadata="https://example.com/.well-known/oauth-protected-resource/contao/mcp", scope="mcp read"',
            $response->headers->get('WWW-Authenticate'),
        );
    }

    public function testAddsAndEscapesTheError(): void
    {
        $response = new BearerChallenge($this->mockResourceContext())->unauthorized('invalid_token', 'The "token" is invalid.');

        $this->assertSame(
            'Bearer resource_metadata="https://example.com/.well-known/oauth-protected-resource/contao/mcp", scope="mcp read", error="invalid_token", error_description="The \\"token\\" is invalid."',
            $response->headers->get('WWW-Authenticate'),
        );
    }

    private function mockResourceContext(): ResourceContext
    {
        $context = $this->createStub(ResourceContext::class);
        $context
            ->method('getProtectedResourceMetadataUrl')
            ->willReturn('https://example.com/.well-known/oauth-protected-resource/contao/mcp')
        ;

        $context
            ->method('getScopes')
            ->willReturn(['mcp', 'read'])
        ;

        return $context;
    }
}
