<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Doctrine\DBAL;

use Contao\CoreBundle\Doctrine\DBAL\ChildQuery;
use Contao\CoreBundle\Doctrine\DBAL\Hierarchy;
use Contao\CoreBundle\Doctrine\DBAL\HierarchyDefinition;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Platforms\MySQLPlatform;
use Doctrine\DBAL\Schema\AbstractSchemaManager;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Types\Types;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class HierarchyTest extends TestCase
{
    public function testGetsUnsortedChildIdsInResultOrder(): void
    {
        $connection = $this->createMock(Connection::class);
        $this->configureConnection($connection);
        $connection
            ->expects($this->exactly(3))
            ->method('fetchAllAssociative')
            ->willReturnOnConsecutiveCalls(
                [
                    ['node_id' => 4, 'parent_id' => 1, 'order_value' => 0],
                    ['node_id' => 3, 'parent_id' => 1, 'order_value' => 0],
                ],
                [
                    ['node_id' => 7, 'parent_id' => 4, 'order_value' => 0],
                    ['node_id' => 5, 'parent_id' => 3, 'order_value' => 0],
                ],
                [],
            )
        ;

        $definition = new HierarchyDefinition('categories', 'category_id', 'parent_category_id');

        $this->assertSame([4, 3, 7, 5], new Hierarchy($connection)->getChildIds(1, $definition));
    }

    public function testGetsChildIdsIteratively(): void
    {
        $connection = $this->createMock(Connection::class);
        $this->configureConnection($connection);
        $connection
            ->expects($this->exactly(3))
            ->method('fetchAllAssociative')
            ->with($this->callback(static fn (string $sql): bool => str_contains($sql, "`tree_type` = 'category'") && str_contains($sql, 'published = 1') && str_contains($sql, '`position` AS order_value')))
            ->willReturnOnConsecutiveCalls(
                [
                    ['node_id' => 3, 'parent_id' => 1, 'order_value' => 20],
                    ['node_id' => 4, 'parent_id' => 1, 'order_value' => 10],
                ],
                [
                    ['node_id' => 5, 'parent_id' => 3, 'order_value' => 10],
                    ['node_id' => 7, 'parent_id' => 4, 'order_value' => 10],
                ],
                [],
            )
        ;

        $query = new ChildQuery()->withOrderBy('position')->withWhere('published = 1');
        $definition = new HierarchyDefinition('categories', 'category_id', 'parent_category_id')->withScope('tree_type', 'category');

        $this->assertSame([4, 7, 3, 5], new Hierarchy($connection)->getChildIds(1, $definition, $query));
    }

    public function testGetsParentIdsUsingUnion(): void
    {
        $connection = $this->createMock(Connection::class);
        $this->configureConnection($connection);
        $connection
            ->expects($this->once())
            ->method('fetchFirstColumn')
            ->with($this->stringContains(' UNION SELECT '), [5])
            ->willReturn([5, 3, 1])
        ;

        $definition = new HierarchyDefinition('categories', 'category_id', 'parent_category_id');

        $this->assertSame([5, 3, 1], new Hierarchy($connection)->getParentIds(5, $definition));
    }

    private function configureConnection(Connection&MockObject $connection): void
    {
        $connection
            ->method('getDatabasePlatform')
            ->willReturn(new MySQLPlatform())
        ;

        $connection
            ->method('quoteIdentifier')
            ->willReturnCallback(static fn (string $identifier): string => "`$identifier`")
        ;

        $table = new Table('categories');
        $table->addColumn('tree_type', Types::STRING);

        $schemaManager = $this->createStub(AbstractSchemaManager::class);
        $schemaManager
            ->method('introspectTable')
            ->willReturn($table)
        ;

        $connection
            ->method('createSchemaManager')
            ->willReturn($schemaManager)
        ;
    }
}
