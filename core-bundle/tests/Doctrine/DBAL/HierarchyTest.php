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
use Contao\CoreBundle\Doctrine\DBAL\ParentQuery;
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

    public function testGetsChildRowsWithAdditionalColumns(): void
    {
        $connection = $this->createMock(Connection::class);
        $this->configureConnection($connection);
        $connection
            ->expects($this->exactly(2))
            ->method('fetchAllAssociative')
            ->with($this->stringContains('`title` AS field_0'))
            ->willReturnOnConsecutiveCalls(
                [['node_id' => 3, 'parent_id' => 1, 'order_value' => 0, 'field_0' => 'Child']],
                [],
            )
        ;

        $definition = new HierarchyDefinition('categories', 'category_id', 'parent_category_id');
        $query = new ChildQuery()->withColumns('title');

        $this->assertSame(
            [['category_id' => 3, 'parent_category_id' => 1, 'title' => 'Child']],
            new Hierarchy($connection)->getChildRows(1, $definition, $query),
        );
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
            ->method('fetchAllAssociative')
            ->with($this->stringContains(' UNION SELECT '), ['local-news'])
            ->willReturn([
                ['node_id' => 'local-news', 'parent_id' => 'news'],
                ['node_id' => 'news', 'parent_id' => 'root'],
                ['node_id' => 'root', 'parent_id' => ''],
            ])
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
            ->method('fetchAllAssociative')
            ->with($this->stringContains(' UNION SELECT '), [5])
            ->willReturn([
                ['node_id' => 5, 'parent_id' => 3],
                ['node_id' => 3, 'parent_id' => 1],
                ['node_id' => 1, 'parent_id' => 0],
            ])
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
            ->method('fetchAllAssociative')
            ->willReturnOnConsecutiveCalls(
                [
                    ['node_id' => 'node-12', 'parent_id' => 'node-11'],
                    ['node_id' => 'node-11', 'parent_id' => 'node-10'],
                    ['node_id' => 'node-10', 'parent_id' => 'node-9'],
                    ['node_id' => 'node-9', 'parent_id' => 'node-8'],
                    ['node_id' => 'node-8', 'parent_id' => 'node-7'],
                    ['node_id' => 'node-7', 'parent_id' => 'node-6'],
                    ['node_id' => 'node-6', 'parent_id' => 'node-5'],
                    ['node_id' => 'node-5', 'parent_id' => 'node-4'],
                    ['node_id' => 'node-4', 'parent_id' => 'node-3'],
                    ['node_id' => 'node-3', 'parent_id' => 'node-2'],
                ],
                [
                    ['node_id' => 'node-3', 'parent_id' => 'node-2'],
                    ['node_id' => 'node-2', 'parent_id' => 'node-1'],
                    ['node_id' => 'node-1', 'parent_id' => ''],
                ],
            )
        ;

        $definition = new HierarchyDefinition('categories', 'category_id', 'parent_category_id');

        $this->assertSame(
            ['node-12', 'node-11', 'node-10', 'node-9', 'node-8', 'node-7', 'node-6', 'node-5', 'node-4', 'node-3', 'node-2', 'node-1'],
            new Hierarchy($connection)->getParentIds('node-12', $definition),
        );
    }

    public function testGetsParentRowsWithAdditionalColumnsAndBoundaryRow(): void
    {
        $connection = $this->createMock(Connection::class);
        $this->configureConnection($connection);
        $connection
            ->expects($this->once())
            ->method('fetchAllAssociative')
            ->with(
                $this->callback(static fn (string $sql): bool => str_contains($sql, '`title` AS field_0') && str_contains($sql, 'AND @continue')),
                [5],
            )
            ->willReturn([
                ['node_id' => 5, 'parent_id' => 3, 'field_0' => 'Nested', 'continue_traversal' => 1],
                ['node_id' => 3, 'parent_id' => 1, 'field_0' => 'Parent', 'continue_traversal' => 1],
                ['node_id' => 1, 'parent_id' => 10, 'field_0' => 'Boundary', 'continue_traversal' => 0],
            ])
        ;

        $definition = new HierarchyDefinition('categories', 'category_id', 'parent_category_id')->withScope('tree_type', 'category');
        $query = new ParentQuery()->withColumns('title')->withBoundaryRow();

        $this->assertSame(
            [
                ['category_id' => 5, 'parent_category_id' => 3, 'title' => 'Nested'],
                ['category_id' => 3, 'parent_category_id' => 1, 'title' => 'Parent'],
                ['category_id' => 1, 'parent_category_id' => 10, 'title' => 'Boundary'],
            ],
            new Hierarchy($connection)->getParentRows(5, $definition, $query),
        );
    }

    public function testLimitsTheParentDepthUsingUnion(): void
    {
        $connection = $this->createMock(Connection::class);
        $this->configureConnection($connection);
        $connection
            ->expects($this->once())
            ->method('fetchAllAssociative')
            ->with($this->logicalNot($this->stringContains(' UNION SELECT ')), [5])
            ->willReturn([['node_id' => 5, 'parent_id' => 3]])
        ;

        $definition = new HierarchyDefinition('categories', 'category_id', 'parent_category_id');
        $query = new ParentQuery()->withMaxDepth(1);

        $this->assertSame(
            [['category_id' => 5, 'parent_category_id' => 3]],
            new Hierarchy($connection)->getParentRows(5, $definition, $query),
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
