<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\ApiBundle\Tests\Http;

use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use Contao\ApiBundle\Http\ApiRequestFactory;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\Routing\Generator\UrlGenerator;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Serializer\Exception\UnsupportedFormatException;

final class ApiRequestFactoryTest extends TestCase
{
    public function testKeepsSessionAndLocaleWithoutLeakingTransportHeaders(): void
    {
        $parent = Request::create('https://example.org/_mcp/backend', 'POST', cookies: ['session' => 'value'], server: [
            'HTTP_MCP_SESSION_ID' => 'mcp-session',
            'HTTP_IF_NONE_MATCH' => 'etag',
            'HTTP_AUTHORIZATION' => 'Bearer token',
            'CONTENT_LENGTH' => 123,
            'REMOTE_ADDR' => '192.0.2.1',
        ], content: '{"jsonrpc":"2.0"}');
        $parent->setLocale('de');
        $parent->setSession(new Session(new MockArraySessionStorage()));
        $parent->attributes->set('_controller', 'mcp');

        $request = $this->createFactory()->create($parent, new Post(name: 'records'), payload: []);

        $this->assertSame('{}', $request->getContent());
        $this->assertSame('POST', $request->getMethod());
        $this->assertSame('application/ld+json', $request->headers->get('Content-Type'));
        $this->assertSame('application/ld+json', $request->headers->get('Accept'));
        $this->assertSame($parent->getSession(), $request->getSession());
        $this->assertSame('de', $request->getLocale());
        $this->assertSame('value', $request->cookies->get('session'));
        $this->assertSame('192.0.2.1', $request->getClientIp());
        $this->assertSame([], $request->attributes->all());

        foreach (['Mcp-Session-Id', 'If-None-Match', 'Authorization', 'Content-Length'] as $header) {
            $this->assertFalse($request->headers->has($header));
        }

        $this->assertSame('{"jsonrpc":"2.0"}', $parent->getContent());
        $this->assertSame('mcp-session', $parent->headers->get('Mcp-Session-Id'));
    }

    public function testGeneratesRoutesWithCustomPrefixAndBaseUrl(): void
    {
        $parent = Request::create('https://example.org/app/index.php/_mcp/backend', server: [
            'SCRIPT_FILENAME' => '/var/www/app/index.php',
            'SCRIPT_NAME' => '/app/index.php',
        ]);
        $factory = $this->createFactory(new RequestContext('/app/index.php'));
        $request = $factory->create($parent, new Get(name: 'unused', routeName: 'records'), ['id' => 'an id', 'page' => 2]);

        $this->assertSame('https://example.org/app/index.php/custom-api/records/an%20id?page=2', $request->getUri());
        $this->assertSame('/custom-api/records/an%20id', $request->getPathInfo());
        $this->assertSame('/app/index.php', $request->getBaseUrl());
        $this->assertSame('2', $request->query->get('page'));
        $this->assertFalse($request->headers->has('Content-Type'));
    }

    public function testUsesOperationFormatsAndMergePatchPayload(): void
    {
        $operation = new Patch(name: 'records', inputFormats: ['json' => ['application/merge-patch+json']], outputFormats: ['json' => ['application/json']]);
        $request = $this->createFactory()->create(Request::create('/_mcp/backend'), $operation, ['id' => 42], ['title' => null]);

        $this->assertSame('application/merge-patch+json', $request->headers->get('Content-Type'));
        $this->assertSame('application/json', $request->headers->get('Accept'));
        $this->assertSame('{"title":null}', $request->getContent());
    }

    public function testRejectsOperationsWithoutJsonOutput(): void
    {
        $this->expectException(UnsupportedFormatException::class);

        $this->createFactory()->create(Request::create('/_mcp/backend'), new Get(name: 'records', outputFormats: ['html' => ['text/html']]));
    }

    private function createFactory(RequestContext|null $context = null): ApiRequestFactory
    {
        $routes = new RouteCollection();
        $routes->add('records', new Route('/custom-api/records/{id}', ['id' => null]));

        return new ApiRequestFactory(new UrlGenerator($routes, $context ?? new RequestContext()));
    }
}
