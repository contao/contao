<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\OAuthServerBundle\Tests;

use Contao\OAuthServerBundle\ResourceContext;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Generator\UrlGenerator;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

final class ResourceContextTest extends TestCase
{
    public function testUsesTheConfiguredResourceRoute(): void
    {
        $context = $this->mockResourceContext();

        $this->assertSame('https://example.com', $context->getIssuer());
        $this->assertSame('https://example.com/contao/mcp', $context->getResource());
        $this->assertSame('/contao/mcp', $context->getResourcePath());
        $this->assertSame('https://example.com/.well-known/oauth-protected-resource/contao/mcp', $context->getProtectedResourceMetadataUrl());
        $this->assertSame(['mcp'], $context->getScopes());
        $this->assertSame('Contao MCP', $context->getResourceName());
    }

    public function testMatchesTheCanonicalResource(): void
    {
        $context = $this->mockResourceContext();

        $this->assertTrue($context->matchesResource('https://example.com/contao/mcp'));
        $this->assertTrue($context->matchesResource('HTTPS://EXAMPLE.COM:443/contao/mcp/'));
        $this->assertFalse($context->matchesResource('https://example.com/contao/mcp#foo'));
        $this->assertFalse($context->matchesResource('https://example.com/api'));
    }

    private function mockResourceContext(): ResourceContext
    {
        $routes = new RouteCollection();
        $routes->add('contao_mcp_backend', new Route('/contao/mcp'));
        $routes->add('contao_oauth_server_protected_resource_path', new Route('/.well-known/oauth-protected-resource/{path}', [], ['path' => '.+']));

        $request = Request::create('https://example.com/contao/mcp');

        $requestStack = new RequestStack();
        $requestStack->push($request);

        $urlGenerator = new UrlGenerator($routes, new RequestContext()->fromRequest($request));

        return new ResourceContext($requestStack, $urlGenerator, 'contao_mcp_backend', ['mcp'], 'Contao MCP');
    }
}
