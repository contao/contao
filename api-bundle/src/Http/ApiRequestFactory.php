<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\ApiBundle\Http;

use ApiPlatform\Metadata\HttpOperation;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Serializer\Exception\UnsupportedFormatException;

/**
 * Builds JSON requests for internal API operations.
 */
final class ApiRequestFactory
{
    public function __construct(private readonly UrlGeneratorInterface $urlGenerator)
    {
    }

    public function create(Request $parent, HttpOperation $operation, array $parameters = [], array|null $payload = null): Request
    {
        $request = $this->createRequest($parent, $operation, $parameters, null === $payload ? null : json_encode((object) $payload, JSON_THROW_ON_ERROR));

        if (null !== $payload) {
            $request->headers->set('Content-Type', $this->getJsonFormat($operation->getInputFormats(), 'PATCH' === $operation->getMethod() ? 'application/merge-patch+json' : 'application/ld+json'));
        }

        return $request;
    }

    public function createRaw(Request $parent, HttpOperation $operation, array $parameters, string $content, string $contentType): Request
    {
        $request = $this->createRequest($parent, $operation, $parameters, $content);
        $request->headers->set('Content-Type', $contentType);

        return $request;
    }

    private function createRequest(Request $parent, HttpOperation $operation, array $parameters, string|null $content): Request
    {
        $uri = $this->urlGenerator->generate($operation->getRouteName() ?? $operation->getName(), $parameters);

        $request = Request::create(
            $parent->getSchemeAndHttpHost().$uri,
            $operation->getMethod(),
            cookies: $parent->cookies->all(),
            server: array_intersect_key($parent->server->all(), array_flip(['SCRIPT_NAME', 'SCRIPT_FILENAME', 'SERVER_PROTOCOL'])),
            content: $content,
        );

        $request->server->set('REMOTE_ADDR', $parent->getClientIp());
        $request->headers->set('Accept', $this->getJsonFormat($operation->getOutputFormats(), 'application/ld+json'));

        if (null === $content) {
            $request->headers->remove('Content-Type');
        }

        // Subrequests share the authenticated token. Copy session and locale for API
        // listeners and voters, without inheriting transport or conditional headers.
        $request->attributes->set('_locale', $parent->getLocale());

        if ($parent->hasSession()) {
            $request->setSession($parent->getSession());
        }

        return $request;
    }

    /**
     * @param array<string, list<string>>|null $formats
     */
    private function getJsonFormat(array|null $formats, string $default): string
    {
        if (null === $formats) {
            return $default;
        }

        foreach ($formats as $mimeTypes) {
            foreach ($mimeTypes as $mimeType) {
                if ('application/json' === $mimeType || str_ends_with($mimeType, '+json')) {
                    return $mimeType;
                }
            }
        }

        throw new UnsupportedFormatException('The API operation does not support a JSON representation.');
    }
}
