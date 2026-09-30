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

    public function create(Request $parent, HttpOperation $operation, array $parameters = [], mixed $payload = null): Request
    {
        $uri = $this->urlGenerator->generate($operation->getRouteName() ?? $operation->getName(), $parameters);
        $contentType = null === $payload ? null : $this->getInputFormat($operation);

        $request = Request::create(
            $parent->getSchemeAndHttpHost().$uri,
            $operation->getMethod(),
            cookies: $parent->cookies->all(),
            server: array_intersect_key($parent->server->all(), array_flip(['SCRIPT_NAME', 'SCRIPT_FILENAME', 'SERVER_PROTOCOL'])),
            content: null === $payload ? null : $this->encodePayload($payload, $contentType),
        );

        $request->server->set('REMOTE_ADDR', $parent->getClientIp());
        $request->headers->set('Accept', $this->getJsonFormat($operation->getOutputFormats(), 'application/ld+json'));

        if (null !== $payload) {
            $request->headers->set('Content-Type', $contentType);
        } else {
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

    private function getInputFormat(HttpOperation $operation): string
    {
        $formats = $operation->getInputFormats();

        if (null === $formats) {
            return 'PATCH' === $operation->getMethod() ? 'application/merge-patch+json' : 'application/ld+json';
        }

        foreach ($formats as $mimeTypes) {
            foreach ($mimeTypes as $mimeType) {
                if ('application/json' === $mimeType || str_ends_with($mimeType, '+json')) {
                    return $mimeType;
                }
            }
        }

        return reset($formats)[0] ?? throw new UnsupportedFormatException('The API operation does not support an input representation.');
    }

    private function encodePayload(mixed $payload, string $contentType): string
    {
        if ('application/json' === $contentType || str_ends_with($contentType, '+json')) {
            $payload = \is_array($payload) && ([] === $payload || !array_is_list($payload)) ? (object) $payload : $payload;

            return json_encode($payload, JSON_THROW_ON_ERROR);
        }

        if (!\is_string($payload)) {
            throw new UnsupportedFormatException(\sprintf('The API operation requires a string payload for "%s".', $contentType));
        }

        return $payload;
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
