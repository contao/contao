<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\OAuthServerBundle\Cimd;

use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\HttpClient\NoPrivateNetworkHttpClient;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * @internal
 */
class ClientIdMetadataResolver
{
    private readonly HttpClientInterface $httpClient;

    public function __construct(
        HttpClientInterface $httpClient,
        private readonly CacheItemPoolInterface $cache,
        private readonly array $trustedDomains,
    ) {
        $this->httpClient = new NoPrivateNetworkHttpClient($httpClient);
    }

    public function resolve(string $clientId): ClientIdMetadataDocument
    {
        $this->assertValidClientId($clientId);

        $item = $this->cache->getItem('contao_oauth_server_cimd_'.hash('xxh128', $clientId));

        if ($item->isHit() && ($cached = $item->get()) instanceof ClientIdMetadataDocument) {
            return $cached;
        }

        $document = $this->parse($clientId, $this->fetch($clientId));

        $item->set($document)->expiresAfter(300);
        $this->cache->save($item);

        return $document;
    }

    private function assertValidClientId(string $clientId): void
    {
        $parts = parse_url($clientId);

        if (false === $parts || 'https' !== ($parts['scheme'] ?? null) || !isset($parts['host']) || \in_array($parts['path'] ?? '', ['', '/'], true) || isset($parts['fragment']) || isset($parts['user'])) {
            throw new CimdException('The client_id is not a valid metadata document URL.');
        }

        $host = strtolower($parts['host']);

        foreach ($this->trustedDomains as $domain) {
            $domain = strtolower($domain);

            if ($host === $domain || str_ends_with($host, '.'.$domain)) {
                return;
            }
        }

        throw new CimdException(\sprintf('The host "%s" is not a trusted client metadata host.', $host));
    }

    /**
     * @return array<string, mixed>
     */
    private function fetch(string $url): array
    {
        try {
            $response = $this->httpClient->request(
                'GET',
                $url,
                [
                    'timeout' => 5,
                    'max_redirects' => 0,
                    'max_duration' => 10,
                    'headers' => ['Accept' => 'application/json'],
                ],
            );

            if (200 !== $response->getStatusCode()) {
                throw new CimdException('Unexpected HTTP status.');
            }

            return $response->toArray();
        } catch (CimdException $e) {
            throw $e;
        } catch (\Throwable $e) {
            throw new CimdException('Could not fetch the client metadata document.', 0, $e);
        }
    }

    /**
     * @param array<string, mixed> $data
     */
    private function parse(string $clientId, array $data): ClientIdMetadataDocument
    {
        if (($data['client_id'] ?? null) !== $clientId) {
            throw new CimdException('The client_id in the document does not match its URL.');
        }

        $name = $data['client_name'] ?? null;
        $redirectUris = $data['redirect_uris'] ?? null;

        if (!\is_string($name) || '' === trim($name)) {
            throw new CimdException('The document has no client_name.');
        }

        if (!\is_array($redirectUris) || [] === $redirectUris || $redirectUris !== array_filter($redirectUris, \is_string(...))) {
            throw new CimdException('The document has no valid redirect_uris.');
        }

        // Metadata documents always describe public clients
        if ('none' !== ($data['token_endpoint_auth_method'] ?? 'none')) {
            throw new CimdException('Only public clients are supported.');
        }

        return new ClientIdMetadataDocument($clientId, mb_substr(trim($name), 0, 100), array_values($redirectUris));
    }
}
