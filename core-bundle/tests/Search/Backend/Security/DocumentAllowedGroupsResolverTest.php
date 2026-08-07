<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Search\Backend\Security;

use Contao\CoreBundle\Search\Backend\Document;
use Contao\CoreBundle\Search\Backend\Provider\ProviderInterface;
use Contao\CoreBundle\Search\Backend\Security\DocumentAccessEvaluator;
use Contao\CoreBundle\Search\Backend\Security\DocumentAllowedGroupsResolver;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use PHPUnit\Framework\TestCase;

class DocumentAllowedGroupsResolverTest extends TestCase
{
    public function testResolvesAllowedGroupsAndCachesUsers(): void
    {
        $connection = $this->createConnection();
        $provider = $this->createStub(ProviderInterface::class);
        $documentAccessEvaluator = $this->createMock(DocumentAccessEvaluator::class);
        $documentAccessEvaluator
            ->expects($this->exactly(4))
            ->method('isGrantedForGroup')
            ->willReturnCallback(
                static fn (ProviderInterface $provider, Document $document, int $groupId): bool => 2 === $groupId,
            )
        ;

        $resolver = new DocumentAllowedGroupsResolver($connection, $documentAccessEvaluator, 2);
        $document = new Document('42', 'type', 'content');

        $this->assertSame([2], $resolver->resolveAllowedGroups($provider, $document));

        $connection->executeStatement('DROP TABLE tl_user_group');

        $this->assertSame([2], $resolver->resolveAllowedGroups($provider, $document));
    }

    public function testDoesNotLimitGroupsIfConfiguredWithZero(): void
    {
        $provider = $this->createStub(ProviderInterface::class);
        $documentAccessEvaluator = $this->createStub(DocumentAccessEvaluator::class);
        $documentAccessEvaluator
            ->method('isGrantedForGroup')
            ->willReturn(true)
        ;

        $resolver = new DocumentAllowedGroupsResolver($this->createConnection(), $documentAccessEvaluator, 0);

        $this->assertSame(
            [1, 2, 3],
            $resolver->resolveAllowedGroups($provider, new Document('42', 'type', 'content')),
        );
    }

    private function createConnection(): Connection
    {
        $connection = DriverManager::getConnection(['driver' => 'pdo_sqlite', 'memory' => true]);
        $connection->executeStatement('CREATE TABLE tl_user_group (id INTEGER, name VARCHAR(255), disable INTEGER, start VARCHAR(10), stop VARCHAR(10))');

        foreach ([[3, 'C', 0], [1, 'A', 0], [2, 'B', 0], [4, 'D', 1]] as [$id, $name, $disable]) {
            $connection->insert('tl_user_group', [
                'id' => $id,
                'name' => $name,
                'disable' => $disable,
                'start' => '',
                'stop' => '',
            ]);
        }

        return $connection;
    }
}
