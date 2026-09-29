<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\OAuthServerBundle\AuthorizationServer\Repository;

use Contao\OAuthServerBundle\AuthorizationServer\Entity\Client;
use Contao\OAuthServerBundle\Cimd\CimdException;
use Contao\OAuthServerBundle\Cimd\ClientIdMetadataResolver;
use League\OAuth2\Server\Entities\ClientEntityInterface;
use League\OAuth2\Server\Repositories\ClientRepositoryInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * @internal
 */
class ClientRepository implements ClientRepositoryInterface
{
    public function __construct(
        private readonly ClientIdMetadataResolver $cimd,
        private readonly RequestStack $requestStack,
        private readonly LoggerInterface|null $logger = null,
    ) {
    }

    public function getClientEntity(string $clientIdentifier): ClientEntityInterface|null
    {
        try {
            $document = $this->cimd->resolve($clientIdentifier);
        } catch (CimdException $e) {
            $this->logger?->info('Rejected OAuth client: '.$e->getMessage(), ['client_id' => $clientIdentifier]);

            return null;
        }

        $redirectUris = $document->redirectUris;
        $requested = $this->requestStack->getCurrentRequest()?->query->get('redirect_uri');

        if (\is_string($requested) && $this->matchesLocalhostIgnoringPort($requested, $redirectUris)) {
            $redirectUris[] = $requested;
        }

        return new Client($document->clientId, $document->clientName, $redirectUris);
    }

    public function validateClient(string $clientIdentifier, string|null $clientSecret, string|null $grantType): bool
    {
        return \in_array($grantType, [null, 'authorization_code', 'refresh_token'], true)
            && $this->getClientEntity($clientIdentifier);
    }

    private function matchesLocalhostIgnoringPort(string $uri, array $allowed): bool
    {
        $parts = parse_url($uri);

        if (!$parts || 'http' !== ($parts['scheme'] ?? null) || 'localhost' !== ($parts['host'] ?? null)) {
            return false;
        }

        $normalized = 'http://localhost'.($parts['path'] ?? '');

        return \in_array($normalized, $allowed, true);
    }
}
