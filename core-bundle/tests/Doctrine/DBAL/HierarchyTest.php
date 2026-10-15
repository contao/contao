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

use Contao\CoreBundle\Doctrine\DBAL\ChildTraversalOptions;
use Contao\CoreBundle\Doctrine\DBAL\Hierarchy;
use Contao\CoreBundle\Doctrine\DBAL\HierarchyDefinition;
use Contao\CoreBundle\Doctrine\DBAL\ParentTraversalOptions;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\ParameterType;
use Doctrine\DBAL\Platforms\MySQL80Platform;
use Doctrine\DBAL\Platforms\MySQLPlatform;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class HierarchyTest extends TestCase
{
    public function testPrunesBranchesOutsideTheScopeOrWhereCondition(): void
    {
        $connection = DriverManager::getConnection(['driver' => 'pdo_sqlite', 'memory' => true]);
        $connection->executeStatement('CREATE TABLE categories (id INTEGER PRIMARY KEY, pid INTEGER, tree_type TEXT, tenant_id INTEGER, title TEXT, published INTEGER)');

        foreach ([
            [2, 1, 'category', 7, "O'Reilly", 1],
            [3, 2, 'category', 7, "O'Reilly", 1],
            [4, 1, 'category', 8, "O'Reilly", 1],
            [5, 4, 'category', 7, "O'Reilly", 1],
            [6, 1, 'other', 7, "O'Reilly", 1],
            [7, 6, 'category', 7, "O'Reilly", 1],
            [8, 1, 'category', 7, "O'Reilly", 0],
            [9, 8, 'category', 7, "O'Reilly", 1],
            [10, 1, 'category', 7, 'Other title', 1],
            [11, 10, 'category', 7, "O'Reilly", 1],
        ] as $row) {
            $connection->executeStatement('INSERT INTO categories VALUES (?, ?, ?, ?, ?, ?)', $row);
        }

        $definition = new HierarchyDefinition('categories', 'id', 'pid')->withScopes(['tree_type' => 'category', 'tenant_id' => 7]);
        $options = new ChildTraversalOptions()->withWhere('title = ? AND published = ?', ["O'Reilly", 1]);

        $this->assertSame([2, 3], new Hierarchy($connection)->getChildIds(1, $definition, $options));
    }

    public function testBindsScopesBeforeMultipleParentStartingIds(): void
    {
        $connection = $this->mockConnection(new MySQL80Platform());
        $connection
            ->expects($this->once())
            ->method('fetchAllAssociative')
            ->with(
                $this->stringContains('WHERE `id` IN (?) AND `tree_type` = ? AND `tenant_id` = ?'),
                ['category', 7, [4, 5], 'category', 7, 'category', 7, 'category', 7],
                [ParameterType::STRING, ParameterType::INTEGER, ArrayParameterType::INTEGER, ParameterType::STRING, ParameterType::INTEGER, ParameterType::STRING, ParameterType::INTEGER, ParameterType::STRING, ParameterType::INTEGER],
            )
            ->willReturn([])
        ;

        $definition = new HierarchyDefinition('categories', 'id', 'pid')->withScopes(['tree_type' => 'category', 'tenant_id' => 7]);

        $this->assertSame([], new Hierarchy($connection)->getParentIds([4, 5], $definition));
    }

    #[DataProvider('platformProvider')]
    public function testBindsChildConditionsAtEveryLevel(MySQLPlatform $platform): void
    {
        $recursive = $platform instanceof MySQL80Platform;
        $parameters = [[1], 'category', 7, "O'Reilly", [2, 3], 1];
        $types = [ArrayParameterType::INTEGER, ParameterType::STRING, ParameterType::INTEGER, ParameterType::STRING, ArrayParameterType::INTEGER, ParameterType::STRING];

        if ($recursive) {
            $parameters = [...$parameters, 'category', 7, "O'Reilly", [2, 3], 1];
            $types = [...$types, ParameterType::STRING, ParameterType::INTEGER, ParameterType::STRING, ArrayParameterType::INTEGER, ParameterType::STRING];
        }

        $connection = $this->mockConnection($platform);
        $connection
            ->expects($this->exactly($recursive ? 1 : 2))
            ->method('fetchAllAssociative')
            ->willReturnCallback(
                function (string $sql, array $params, array $parameterTypes) use ($parameters, $types, $recursive): array {
                    $this->assertStringContainsString('`tree_type` = ? AND `tenant_id` = ?', $sql);
                    $this->assertSame($recursive ? 2 : 1, substr_count($sql, 'title = ? AND status IN (?) AND published = ?'));
                    $this->assertSame($types, $parameterTypes);
                    $this->assertSame(\array_slice($parameters, 1), \array_slice($params, 1));
                    $this->assertContains($params[0], [[1], [4]]);

                    return [1] === $params[0] ? [['node_id' => 4, 'parent_id' => 1, 'order_value' => 0]] : [];
                },
            )
        ;

        $definition = new HierarchyDefinition('categories', 'id', 'pid')->withScopes(['tree_type' => 'category', 'tenant_id' => 7]);
        $options = new ChildTraversalOptions()->withWhere(
            'title = ? AND status IN (?) AND published = ?',
            ["O'Reilly", [2, 3], 1],
            [ParameterType::STRING, ArrayParameterType::INTEGER],
        );

        $this->assertSame([4], new Hierarchy($connection)->getChildIds(1, $definition, $options));
    }

    #[DataProvider('platformProvider')]
    public function testAppliesAllScopesToParentTraversal(MySQLPlatform $platform): void
    {
        $connection = $this->mockConnection($platform);
        $connection
            ->expects($this->once())
            ->method('fetchAllAssociative')
            ->willReturnCallback(
                function (string $sql, array $parameters) use ($platform): array {
                    $this->assertStringContainsString('WHERE `id` = ? AND `tree_type` = ? AND `tenant_id` = ?', $sql);
                    $this->assertStringContainsString(
                        $platform instanceof MySQL80Platform
                            ? 'WHERE 1 = 1 AND parent.`tree_type` = ? AND parent.`tenant_id` = ?'
                            : 'WHERE `id` = @parent_id AND `tree_type` = ? AND `tenant_id` = ?',
                        $sql,
                    );
                    $this->assertSame(
                        $platform instanceof MySQL80Platform
                            ? ['category', 7, 4, 'category', 7, 'category', 7, 'category', 7]
                            : ['category', 7, 4, ...array_merge(...array_fill(0, 19, ['category', 7]))],
                        $parameters,
                    );

                    return [['node_id' => 4, 'parent_id' => 0]];
                },
            )
        ;

        $definition = new HierarchyDefinition('categories', 'id', 'pid')->withScopes(['tree_type' => 'category', 'tenant_id' => 7]);

        $this->assertSame([4], new Hierarchy($connection)->getParentIds(4, $definition));
    }

    #[DataProvider('platformProvider')]
    public function testUsesAllScopesToDetectParentBoundary(MySQLPlatform $platform): void
    {
        $connection = $this->mockConnection($platform);
        $connection
            ->expects($this->once())
            ->method('fetchAllAssociative')
            ->willReturnCallback(
                function (string $sql, array $parameters) use ($platform): array {
                    $this->assertStringContainsString('`tree_type` = ? AND `tenant_id` = ?', $sql);
                    $this->assertStringContainsString('WHERE `id` = ?', $sql);
                    $this->assertStringNotContainsString('WHERE `id` = ? AND', $sql);
                    $this->assertSame(
                        $platform instanceof MySQL80Platform
                            ? ['category', 7, 4, 'category', 7]
                            : ['category', 7, 4, ...array_merge(...array_fill(0, 9, ['category', 7]))],
                        $parameters,
                    );

                    return [
                        ['node_id' => 4, 'parent_id' => 2, 'continue_traversal' => 1],
                        ['node_id' => 2, 'parent_id' => 10, 'continue_traversal' => 0],
                    ];
                },
            )
        ;

        $definition = new HierarchyDefinition('categories', 'id', 'pid')->withScopes(['tree_type' => 'category', 'tenant_id' => 7]);
        $options = new ParentTraversalOptions()->withBoundaryRow();

        $this->assertSame([['id' => 4, 'pid' => 2], ['id' => 2, 'pid' => 10]], new Hierarchy($connection)->getParentRows(4, $definition, $options));
    }

    public static function platformProvider(): iterable
    {
        yield 'iterative and union' => [new MySQLPlatform()];
        yield 'recursive CTE' => [new MySQL80Platform()];
    }

    public function testGetsUnsortedChildIdsInResultOrder(): void
    {
        $connection = $this->mockConnection();
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
        $connection = $this->mockConnection();
        $connection
            ->expects($this->exactly(3))
            ->method('fetchAllAssociative')
            ->with($this->callback(static fn (string $sql): bool => str_contains($sql, '`tree_type` = ?') && str_contains($sql, 'published = 1') && str_contains($sql, '`position` AS order_value')))
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

        $options = new ChildTraversalOptions()->withOrderBy('position')->withWhere('published = 1');
        $definition = new HierarchyDefinition('categories', 'category_id', 'parent_category_id')->withScopes(['tree_type' => 'category']);

        $this->assertSame([4, 7, 3, 5], new Hierarchy($connection)->getChildIds(1, $definition, $options));
    }

    public function testGetsChildRowsWithAdditionalColumns(): void
    {
        $connection = $this->mockConnection();
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
        $options = new ChildTraversalOptions()->withColumns('title');

        $this->assertSame(
            [['category_id' => 3, 'parent_category_id' => 1, 'title' => 'Child']],
            new Hierarchy($connection)->getChildRows(1, $definition, $options),
        );
    }

    public function testGetsChildRowsWithAllColumns(): void
    {
        $connection = $this->mockConnection();
        $connection
            ->expects($this->exactly(2))
            ->method('fetchAllAssociative')
            ->willReturnOnConsecutiveCalls(
                [['node_id' => 3, 'parent_id' => 1, 'order_value' => 0]],
                [['category_id' => 3, 'parent_category_id' => 1, 'title' => 'Child', 'published' => 1]],
            )
        ;

        $definition = new HierarchyDefinition('categories', 'category_id', 'parent_category_id');
        $options = new ChildTraversalOptions()->withMaxDepth(1)->withAllColumns();

        $this->assertSame(
            [['category_id' => 3, 'parent_category_id' => 1, 'title' => 'Child', 'published' => 1]],
            new Hierarchy($connection)->getChildRows(1, $definition, $options),
        );
    }

    public function testLimitsTheChildDepthIteratively(): void
    {
        $connection = $this->mockConnection();
        $connection
            ->expects($this->once())
            ->method('fetchAllAssociative')
            ->willReturn([
                ['node_id' => 3, 'parent_id' => 1, 'order_value' => 0],
                ['node_id' => 4, 'parent_id' => 1, 'order_value' => 0],
            ])
        ;

        $definition = new HierarchyDefinition('categories', 'category_id', 'parent_category_id');
        $options = new ChildTraversalOptions()->withMaxDepth(1);

        $this->assertSame([3, 4], new Hierarchy($connection)->getChildIds(1, $definition, $options));
    }

    public function testLimitsTheChildDepthUsingACommonTableExpression(): void
    {
        $connection = $this->mockConnection(new MySQL80Platform());
        $connection
            ->expects($this->once())
            ->method('fetchAllAssociative')
            ->with($this->stringContains('parent.depth < 1'))
            ->willReturn([
                ['node_id' => 3, 'parent_id' => 1, 'order_value' => 0],
                ['node_id' => 4, 'parent_id' => 1, 'order_value' => 0],
            ])
        ;

        $definition = new HierarchyDefinition('categories', 'category_id', 'parent_category_id');
        $options = new ChildTraversalOptions()->withMaxDepth(1);

        $this->assertSame([3, 4], new Hierarchy($connection)->getChildIds(1, $definition, $options));
    }

    #[DataProvider('nestedParentIdsProvider')]
    public function testGetsSortedChildIdsWithNestedParentIds(array $parentIds): void
    {
        $connection = $this->mockConnection();
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
        $options = new ChildTraversalOptions()->withOrderBy('position');

        $this->assertSame([4, 7, 3, 5], new Hierarchy($connection)->getChildIds($parentIds, $definition, $options));
    }

    public static function nestedParentIdsProvider(): iterable
    {
        yield 'root before child' => [[1, 4]];
        yield 'child before root' => [[4, 1]];
    }

    public function testGetsParentIdsUsingUnion(): void
    {
        $connection = $this->mockConnection();
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
        $connection = $this->mockConnection();
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

    public function testGetsParentIdsForMultipleBranchesUsingUnion(): void
    {
        $connection = $this->mockConnection();
        $connection
            ->expects($this->exactly(2))
            ->method('fetchAllAssociative')
            ->willReturnOnConsecutiveCalls(
                [
                    ['node_id' => 5, 'parent_id' => 3],
                    ['node_id' => 3, 'parent_id' => 1],
                    ['node_id' => 1, 'parent_id' => 0],
                ],
                [
                    ['node_id' => 4, 'parent_id' => 1],
                    ['node_id' => 1, 'parent_id' => 0],
                ],
            )
        ;

        $definition = new HierarchyDefinition('categories', 'category_id', 'parent_category_id');

        $this->assertSame([5, 3, 1, 4], new Hierarchy($connection)->getParentIds([5, 4], $definition));
    }

    public function testGetsParentIdsForMultipleBranchesUsingACommonTableExpression(): void
    {
        $connection = $this->mockConnection(new MySQL80Platform());
        $connection
            ->expects($this->once())
            ->method('fetchAllAssociative')
            ->with(
                $this->stringContains('WHERE `category_id` IN (?)'),
                [[5, 4]],
                [ArrayParameterType::INTEGER],
            )
            ->willReturn([
                ['node_id' => 1, 'parent_id' => 0],
                ['node_id' => 4, 'parent_id' => 1],
                ['node_id' => 3, 'parent_id' => 1],
                ['node_id' => 5, 'parent_id' => 3],
            ])
        ;

        $definition = new HierarchyDefinition('categories', 'category_id', 'parent_category_id');
        $hierarchy = new Hierarchy($connection);

        $this->assertSame([5, 3, 1, 4], $hierarchy->getParentIds([5, 4], $definition));
        $this->assertSame([], $hierarchy->getParentIds([], $definition));
    }

    public function testGetsIndividualParentIdTrailsInOneQuery(): void
    {
        $connection = $this->mockConnection(new MySQL80Platform());
        $connection
            ->expects($this->once())
            ->method('fetchAllAssociative')
            ->willReturn([
                ['node_id' => 1, 'parent_id' => 0],
                ['node_id' => 3, 'parent_id' => 1],
                ['node_id' => 5, 'parent_id' => 3],
                ['node_id' => 4, 'parent_id' => 1],
            ])
        ;

        $definition = new HierarchyDefinition('categories', 'category_id', 'parent_category_id');
        $hierarchy = new Hierarchy($connection);

        $this->assertSame([[3, 1], [1]], $hierarchy->getParentIdTrails([5, 4], $definition, true));
    }

    public function testSkipsNestedStartingIds(): void
    {
        $connection = $this->mockConnection(new MySQL80Platform());
        $connection
            ->expects($this->once())
            ->method('fetchAllAssociative')
            ->willReturn([
                ['node_id' => 1, 'parent_id' => 0],
                ['node_id' => 3, 'parent_id' => 1],
                ['node_id' => 5, 'parent_id' => 3],
            ])
        ;

        $definition = new HierarchyDefinition('categories', 'category_id', 'parent_category_id');

        $this->assertSame([1], new Hierarchy($connection)->getParentIds([5, 3], $definition, true));
    }

    public function testGetsMoreThanTenParentIdsUsingUnion(): void
    {
        $connection = $this->mockConnection();
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
        $connection = $this->mockConnection();
        $connection
            ->expects($this->once())
            ->method('fetchAllAssociative')
            ->with(
                $this->callback(static fn (string $sql): bool => str_contains($sql, '`title` AS field_0') && str_contains($sql, 'AND @continue')),
                ['category', 5, ...array_fill(0, 9, 'category')],
            )
            ->willReturn([
                ['node_id' => 5, 'parent_id' => 3, 'field_0' => 'Nested', 'continue_traversal' => 1],
                ['node_id' => 3, 'parent_id' => 1, 'field_0' => 'Parent', 'continue_traversal' => 1],
                ['node_id' => 1, 'parent_id' => 10, 'field_0' => 'Boundary', 'continue_traversal' => 0],
            ])
        ;

        $definition = new HierarchyDefinition('categories', 'category_id', 'parent_category_id')->withScopes(['tree_type' => 'category']);
        $options = new ParentTraversalOptions()->withColumns('title')->withBoundaryRow();

        $this->assertSame(
            [
                ['category_id' => 5, 'parent_category_id' => 3, 'title' => 'Nested'],
                ['category_id' => 3, 'parent_category_id' => 1, 'title' => 'Parent'],
                ['category_id' => 1, 'parent_category_id' => 10, 'title' => 'Boundary'],
            ],
            new Hierarchy($connection)->getParentRows(5, $definition, $options),
        );
    }

    public function testGetsParentRowsWithAllColumns(): void
    {
        $connection = $this->mockConnection();
        $connection
            ->expects($this->exactly(2))
            ->method('fetchAllAssociative')
            ->willReturnOnConsecutiveCalls(
                [['node_id' => 3, 'parent_id' => 1]],
                [['category_id' => 3, 'parent_category_id' => 1, 'title' => 'Child', 'published' => 1]],
            )
        ;

        $definition = new HierarchyDefinition('categories', 'category_id', 'parent_category_id');
        $options = new ParentTraversalOptions()->withMaxDepth(1)->withAllColumns();

        $this->assertSame(
            [['category_id' => 3, 'parent_category_id' => 1, 'title' => 'Child', 'published' => 1]],
            new Hierarchy($connection)->getParentRows(3, $definition, $options),
        );
    }

    public function testGetsParentRowsWithAllColumnsUsingACommonTableExpression(): void
    {
        $connection = $this->mockConnection(new MySQL80Platform());
        $connection
            ->expects($this->once())
            ->method('fetchAllAssociative')
            ->with($this->stringContains('SELECT source.* FROM contao_tree INNER JOIN `categories` source'))
            ->willReturn([
                ['category_id' => 1, 'parent_category_id' => 0, 'title' => 'Parent', 'published' => 1],
                ['category_id' => 3, 'parent_category_id' => 1, 'title' => 'Child', 'published' => 1],
            ])
        ;

        $definition = new HierarchyDefinition('categories', 'category_id', 'parent_category_id');

        $this->assertSame(
            [
                ['category_id' => 3, 'parent_category_id' => 1, 'title' => 'Child', 'published' => 1],
                ['category_id' => 1, 'parent_category_id' => 0, 'title' => 'Parent', 'published' => 1],
            ],
            new Hierarchy($connection)->getParentRows(3, $definition, new ParentTraversalOptions()->withAllColumns()),
        );
    }

    public function testLimitsTheParentDepthUsingUnion(): void
    {
        $connection = $this->mockConnection();
        $connection
            ->expects($this->once())
            ->method('fetchAllAssociative')
            ->with($this->logicalNot($this->stringContains(' UNION SELECT ')), [5])
            ->willReturn([['node_id' => 5, 'parent_id' => 3]])
        ;

        $definition = new HierarchyDefinition('categories', 'category_id', 'parent_category_id');
        $options = new ParentTraversalOptions()->withMaxDepth(1);

        $this->assertSame(
            [['category_id' => 5, 'parent_category_id' => 3]],
            new Hierarchy($connection)->getParentRows(5, $definition, $options),
        );
    }

    private function mockConnection(MySQLPlatform|null $platform = null): Connection&MockObject
    {
        $connection = $this->createMock(Connection::class);
        $connection
            ->method('getDatabasePlatform')
            ->willReturn($platform ?? new MySQLPlatform())
        ;

        $connection
            ->method('quoteIdentifier')
            ->willReturnCallback(static fn (string $identifier): string => "`$identifier`")
        ;

        return $connection;
    }
}
