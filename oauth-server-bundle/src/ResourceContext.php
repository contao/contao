<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\OAuthServerBundle;

use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * @internal
 */
class ResourceContext
{
    /**
     * @param list<string> $scopes
     */
    public function __construct(
        private readonly RequestStack $requestStack,
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly string $resourceRoute,
        private readonly array $scopes,
        private readonly string $resourceName,
    ) {
    }

    public function getIssuer(): string
    {
        $request = $this->requestStack->getMainRequest() ?? throw new \LogicException('No request available.');

        return $request->getSchemeAndHttpHost();
    }

    public function getResource(): string
    {
        return $this->urlGenerator->generate($this->resourceRoute, [], UrlGeneratorInterface::ABSOLUTE_URL);
    }

    public function getProtectedResourceMetadataUrl(): string
    {
        return $this->urlGenerator->generate(
            'contao_oauth_server_protected_resource_path',
            ['path' => ltrim($this->getResourcePath(), '/')],
            UrlGeneratorInterface::ABSOLUTE_URL,
        );
    }

    public function getResourcePath(): string
    {
        return $this->urlGenerator->generate($this->resourceRoute);
    }

    /**
     * @return list<string>
     */
    public function getScopes(): array
    {
        return $this->scopes;
    }

    public function getResourceName(): string
    {
        return $this->resourceName;
    }

    public function matchesResource(string $resource): bool
    {
        return self::canonicalize($resource) === self::canonicalize($this->getResource());
    }

    private static function canonicalize(string $uri): string|null
    {
        $parts = parse_url($uri);

        if (false === $parts || !isset($parts['scheme'], $parts['host']) || isset($parts['fragment'])) {
            return null;
        }

        $scheme = strtolower($parts['scheme']);
        $port = $parts['port'] ?? null;

        if (('https' === $scheme && 443 === $port) || ('http' === $scheme && 80 === $port)) {
            $port = null;
        }

        return $scheme.'://'.strtolower($parts['host']).($port ? ':'.$port : '').rtrim($parts['path'] ?? '', '/');
    }
}
