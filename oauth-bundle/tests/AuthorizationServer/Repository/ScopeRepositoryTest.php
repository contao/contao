<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\OAuthBundle\Tests\AuthorizationServer\Repository;

use Contao\OAuthBundle\AuthorizationServer\Repository\ScopeRepository;
use Contao\OAuthBundle\ResourceContext;
use League\OAuth2\Server\Entities\ClientEntityInterface;
use League\OAuth2\Server\Entities\ScopeEntityInterface;
use PHPUnit\Framework\TestCase;

final class ScopeRepositoryTest extends TestCase
{
    public function testOnlyKnowsTheConfiguredScopes(): void
    {
        $repository = new ScopeRepository($this->mockResourceContext());

        $this->assertSame('mcp read', $repository->getDefaultScope());
        $this->assertSame('read', $repository->getScopeEntityByIdentifier('read')?->getIdentifier());
        $this->assertNull($repository->getScopeEntityByIdentifier('write'));

        $scopes = $repository->finalizeScopes([], 'authorization_code', $this->createStub(ClientEntityInterface::class));

        $this->assertSame(['mcp', 'read'], array_map(static fn (ScopeEntityInterface $scope): string => $scope->getIdentifier(), $scopes));
    }

    private function mockResourceContext(): ResourceContext
    {
        $context = $this->createStub(ResourceContext::class);
        $context
            ->method('getScopes')
            ->willReturn(['mcp', 'read'])
        ;

        return $context;
    }
}
