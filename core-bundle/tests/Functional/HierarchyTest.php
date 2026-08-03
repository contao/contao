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

use Contao\CoreBundle\Doctrine\DBAL\ChildQuery;
use Contao\CoreBundle\Doctrine\DBAL\Hierarchy;
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
        $ids = $this->hierarchy->getChildIds([0, 1], 'tl_page');
        sort($ids);

        $this->assertSame([3, 4, 5, 7], $ids);
        $query = new ChildQuery()->withOrderBySorting();

        $this->assertSame([4, 7, 3, 5], $this->hierarchy->getChildIds(1, 'tl_page', $query));
        $this->assertSame([4, 7], $this->hierarchy->getChildIds(1, 'tl_page', $query->withWhere('id != 3')));
        $this->assertSame([], $this->hierarchy->getChildIds(0, 'tl_page'));
        $this->assertSame([9, 8], $this->hierarchy->getChildIds(8, 'tl_page'));
    }

    public function testGetsParentIdsUsingARecursiveCommonTableExpression(): void
    {
        $this->assertSame([5, 3, 1], $this->hierarchy->getParentIds(5, 'tl_page'));
        $this->assertSame([3, 1], $this->hierarchy->getParentIds(5, 'tl_page', true));
        $this->assertSame([], $this->hierarchy->getParentIds(99, 'tl_page'));
        $this->assertSame([8, 9], $this->hierarchy->getParentIds(8, 'tl_page'));
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
                array_combine(['id', 'pid', 'sorting'], $row),
            );
        }
    }
}
