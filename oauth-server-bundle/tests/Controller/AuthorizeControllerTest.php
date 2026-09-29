<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\OAuthServerBundle\Tests\Controller;

use Contao\BackendUser;
use Contao\OAuthServerBundle\AuthorizationServer\AuthorizationServerFactory;
use Contao\OAuthServerBundle\Controller\AuthorizeController;
use Contao\OAuthServerBundle\Http\PsrMessageConverter;
use Contao\OAuthServerBundle\ResourceContext;
use League\OAuth2\Server\AuthorizationServer;
use League\OAuth2\Server\Exception\OAuthServerException;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Twig\Environment;

final class AuthorizeControllerTest extends TestCase
{
    public function testAddsTheIssuerToRedirectedValidationErrors(): void
    {
        $server = $this->createStub(AuthorizationServer::class);
        $server
            ->method('validateAuthorizationRequest')
            ->willThrowException(OAuthServerException::invalidScope('foo', 'https://client.example/callback'))
        ;

        $response = $this->mockController($server)(Request::create('https://example.com/contao/oauth/authorize'));

        $this->assertSame(Response::HTTP_FOUND, $response->getStatusCode());

        $query = [];
        parse_str((string) parse_url((string) $response->headers->get('Location'), PHP_URL_QUERY), $query);

        $this->assertSame('invalid_scope', $query['error']);
        $this->assertSame('https://example.com', $query['iss']);
    }

    public function testDoesNotAddTheIssuerToValidationErrorsWithoutRedirect(): void
    {
        $server = $this->createStub(AuthorizationServer::class);
        $server
            ->method('validateAuthorizationRequest')
            ->willThrowException(OAuthServerException::invalidClient($this->createStub(ServerRequestInterface::class)))
        ;

        $response = $this->mockController($server)(Request::create('https://example.com/contao/oauth/authorize'));

        $this->assertSame(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());
        $this->assertFalse($response->headers->has('Location'));
    }

    private function mockController(AuthorizationServer $server): AuthorizeController
    {
        $serverFactory = $this->createStub(AuthorizationServerFactory::class);
        $serverFactory
            ->method('create')
            ->willReturn($server)
        ;

        $context = $this->createStub(ResourceContext::class);
        $context
            ->method('getIssuer')
            ->willReturn('https://example.com')
        ;

        $security = $this->createStub(Security::class);
        $security
            ->method('getUser')
            ->willReturn($this->createStub(BackendUser::class))
        ;

        return new AuthorizeController(
            $serverFactory,
            new PsrMessageConverter(),
            $context,
            $security,
            $this->createStub(Environment::class),
            $this->createStub(CsrfTokenManagerInterface::class),
            'contao_csrf_token',
        );
    }
}
