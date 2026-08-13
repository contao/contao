<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Functional;

use Contao\CoreBundle\Doctrine\DBAL\ChildTraversalOptions;
use Contao\CoreBundle\Doctrine\DBAL\Hierarchy;
use Contao\CoreBundle\Doctrine\DBAL\HierarchyDefinition;
use Contao\CoreBundle\Doctrine\DBAL\ParentTraversalOptions;
use Contao\TestCase\FunctionalTestCase;
use Doctrine\DBAL\Connection;

class HierarchyTest extends FunctionalTestCase
{
    private Connection $connection;

    private Hierarchy $hierarchy;

    protected function setUp(): void
    {
        parent::setUp();

        $container = self::createClient()->getContainer();
        $this->connection = $container->get('database_connection');
        $this->hierarchy = $container->get('contao.doctrine.dbal.hierarchy');

        self::resetDatabaseSchema();
        $this->insertRows();
    }

    protected function tearDown(): void
    {
        self::resetDatabaseSchema();

        parent::tearDown();
    }

    public function testGetsChildIdsUsingARecursiveCommonTableExpression(): void
    {
        $definition = new HierarchyDefinition('tl_page', 'id', 'pid');
        $ids = $this->hierarchy->getChildIds(1, $definition);
        sort($ids);

        $this->assertSame([3, 4, 5, 7], $ids);
        $options = new ChildTraversalOptions()->withOrderBy('sorting');

        $this->assertSame([4, 7, 3, 5], $this->hierarchy->getChildIds(1, $definition, $options));
        $this->assertSame([4, 7, 3, 5], $this->hierarchy->getChildIds([1, 4], $definition, $options));
        $this->assertSame([4, 7, 3, 5], $this->hierarchy->getChildIds([4, 1], $definition, $options));
        $this->assertSame([4, 7], $this->hierarchy->getChildIds(1, $definition, $options->withWhere('id != 3')));
        $this->assertSame([4, 3], $this->hierarchy->getChildIds(1, $definition, $options->withMaxDepth(1)));
        $rootIds = $this->hierarchy->getChildIds(0, $definition);
        sort($rootIds);

        $this->assertSame([1, 2, 3, 4, 5, 7], $rootIds);
        $this->assertSame([9, 8], $this->hierarchy->getChildIds(8, $definition));
    }

    public function testGetsChildRowsUsingARecursiveCommonTableExpression(): void
    {
        $definition = new HierarchyDefinition('tl_page', 'id', 'pid');
        $options = new ChildTraversalOptions()->withOrderBy('sorting')->withColumns('title');

        $this->assertSame(
            [
                ['id' => 4, 'pid' => 1, 'title' => 'Page 4'],
                ['id' => 7, 'pid' => 4, 'title' => 'Page 7'],
                ['id' => 3, 'pid' => 1, 'title' => 'Page 3'],
                ['id' => 5, 'pid' => 3, 'title' => 'Page 5'],
            ],
            $this->hierarchy->getChildRows(1, $definition, $options),
        );

        $rows = $this->hierarchy->getChildRows(1, $definition, $options->withMaxDepth(1)->withAllColumns());

        $this->assertSame([4, 3], array_column($rows, 'id'));
        $this->assertSame([10, 20], array_column($rows, 'sorting'));
        $this->assertSame(['Page 4', 'Page 3'], array_column($rows, 'title'));
    }

    public function testGetsParentIdsUsingARecursiveCommonTableExpression(): void
    {
        $definition = new HierarchyDefinition('tl_page', 'id', 'pid');

        $this->assertSame([5, 3, 1], $this->hierarchy->getParentIds(5, $definition));
        $this->assertSame([3, 1], $this->hierarchy->getParentIds(5, $definition, true));
        $this->assertSame([], $this->hierarchy->getParentIds(99, $definition));
        $this->assertSame([8, 9], $this->hierarchy->getParentIds(8, $definition));
    }

    public function testGetsParentRowsUsingARecursiveCommonTableExpression(): void
    {
        $definition = new HierarchyDefinition('tl_page', 'id', 'pid');
        $options = new ParentTraversalOptions()->withColumns('title');

        $this->assertSame(
            [
                ['id' => 5, 'pid' => 3, 'title' => 'Page 5'],
                ['id' => 3, 'pid' => 1, 'title' => 'Page 3'],
                ['id' => 1, 'pid' => 0, 'title' => 'Page 1'],
            ],
            $this->hierarchy->getParentRows(5, $definition, $options),
        );
        $this->assertSame(
            [['id' => 5, 'pid' => 3, 'title' => 'Page 5']],
            $this->hierarchy->getParentRows(5, $definition, $options->withMaxDepth(1)),
        );

        $rows = $this->hierarchy->getParentRows(5, $definition, $options->withMaxDepth(1)->withAllColumns());

        $this->assertSame([5], array_column($rows, 'id'));
        $this->assertSame([10], array_column($rows, 'sorting'));
        $this->assertSame(['Page 5'], array_column($rows, 'title'));
    }

    public function testIncludesTheFirstParentRowOutsideTheScope(): void
    {
        $definition = new HierarchyDefinition('tl_content', 'id', 'pid')->withScope('ptable', 'tl_content');
        $options = new ParentTraversalOptions()->withColumns('ptable')->withBoundaryRow();

        $this->assertSame(
            [
                ['id' => 12, 'pid' => 11, 'ptable' => 'tl_content'],
                ['id' => 11, 'pid' => 10, 'ptable' => 'tl_content'],
                ['id' => 10, 'pid' => 42, 'ptable' => 'tl_article'],
            ],
            $this->hierarchy->getParentRows(12, $definition, $options),
        );
    }

    private function insertRows(): void
    {
        foreach ([
            [1, 0, 10],
            [2, 0, 20],
            [3, 1, 20],
            [4, 1, 10],
            [5, 3, 10],
            [7, 4, 10],
            [8, 9, 10],
            [9, 8, 10],
        ] as $row) {
            $this->connection->insert(
                'tl_page',
                [...array_combine(['id', 'pid', 'sorting'], $row), 'title' => 'Page '.$row[0]],
            );
        }

        foreach ([
            [10, 42, 'tl_article'],
            [11, 10, 'tl_content'],
            [12, 11, 'tl_content'],
        ] as $row) {
            $this->connection->insert('tl_content', array_combine(['id', 'pid', 'ptable'], $row));
        }
    }
}
