<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\OAuthBundle\AuthorizationServer\Repository;

use Contao\OAuthBundle\AuthorizationServer\Entity\Scope;
use Contao\OAuthBundle\ResourceContext;
use League\OAuth2\Server\Entities\ClientEntityInterface;
use League\OAuth2\Server\Entities\ScopeEntityInterface;
use League\OAuth2\Server\Repositories\ScopeRepositoryInterface;

/**
 * @internal
 */
class ScopeRepository implements ScopeRepositoryInterface
{
    public function __construct(private readonly ResourceContext $context)
    {
    }

    public function getDefaultScope(): string
    {
        return implode(' ', $this->context->getScopes());
    }

    public function getScopeEntityByIdentifier(string $identifier): ScopeEntityInterface|null
    {
        return \in_array($identifier, $this->context->getScopes(), true) ? new Scope($identifier) : null;
    }

    public function finalizeScopes(array $scopes, string $grantType, ClientEntityInterface $clientEntity, string|null $userIdentifier = null, string|null $authCodeId = null): array
    {
        return array_map(static fn (string $scope): Scope => new Scope($scope), $this->context->getScopes());
    }
}
