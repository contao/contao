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
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Platforms\MySQLPlatform;
use Doctrine\DBAL\Schema\AbstractSchemaManager;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Types\Types;
use PHPUnit\Framework\Attributes\DataProvider;
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
            ->with($this->anything(), $this->anything(), [ArrayParameterType::STRING])
            ->willReturnOnConsecutiveCalls(
                [
                    ['node_id' => 'news', 'parent_id' => 'root', 'order_value' => 0],
                    ['node_id' => 'events', 'parent_id' => 'root', 'order_value' => 0],
                ],
                [
                    ['node_id' => 'local-news', 'parent_id' => 'news', 'order_value' => 0],
                    ['node_id' => 'upcoming-events', 'parent_id' => 'events', 'order_value' => 0],
                ],
                [],
            )
        ;

        $definition = new HierarchyDefinition('categories', 'category_id', 'parent_category_id');

        $this->assertSame(
            ['news', 'events', 'local-news', 'upcoming-events'],
            new Hierarchy($connection)->getChildIds('root', $definition),
        );
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

    #[DataProvider('nestedParentIdsProvider')]
    public function testGetsSortedChildIdsWithNestedParentIds(array $parentIds): void
    {
        $connection = $this->createMock(Connection::class);
        $this->configureConnection($connection);
        $connection
            ->expects($this->exactly(3))
            ->method('fetchAllAssociative')
            ->willReturnOnConsecutiveCalls(
                [
                    ['node_id' => 3, 'parent_id' => 1, 'order_value' => 20],
                    ['node_id' => 4, 'parent_id' => 1, 'order_value' => 10],
                    ['node_id' => 7, 'parent_id' => 4, 'order_value' => 10],
                ],
                [
                    ['node_id' => 5, 'parent_id' => 3, 'order_value' => 10],
                ],
                [],
            )
        ;

        $definition = new HierarchyDefinition('categories', 'category_id', 'parent_category_id');
        $query = new ChildQuery()->withOrderBy('position');

        $this->assertSame([4, 7, 3, 5], new Hierarchy($connection)->getChildIds($parentIds, $definition, $query));
    }

    public static function nestedParentIdsProvider(): iterable
    {
        yield 'root before child' => [[1, 4]];
        yield 'child before root' => [[4, 1]];
    }

    public function testGetsParentIdsUsingUnion(): void
    {
        $connection = $this->createMock(Connection::class);
        $this->configureConnection($connection);
        $connection
            ->expects($this->once())
            ->method('fetchFirstColumn')
            ->with($this->stringContains(' UNION SELECT '), ['local-news'])
            ->willReturn(['local-news', 'news', 'root'])
        ;

        $definition = new HierarchyDefinition('categories', 'category_id', 'parent_category_id');

        $this->assertSame(['local-news', 'news', 'root'], new Hierarchy($connection)->getParentIds('local-news', $definition));
    }

    public function testGetsIntegerParentIdsUsingUnion(): void
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

    public function testGetsMoreThanTenParentIdsUsingUnion(): void
    {
        $connection = $this->createMock(Connection::class);
        $this->configureConnection($connection);
        $connection
            ->expects($this->exactly(2))
            ->method('fetchFirstColumn')
            ->willReturnOnConsecutiveCalls(
                ['node-12', 'node-11', 'node-10', 'node-9', 'node-8', 'node-7', 'node-6', 'node-5', 'node-4', 'node-3'],
                ['node-3', 'node-2', 'node-1'],
            )
        ;

        $definition = new HierarchyDefinition('categories', 'category_id', 'parent_category_id');

        $this->assertSame(
            ['node-12', 'node-11', 'node-10', 'node-9', 'node-8', 'node-7', 'node-6', 'node-5', 'node-4', 'node-3', 'node-2', 'node-1'],
            new Hierarchy($connection)->getParentIds('node-12', $definition),
        );
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
