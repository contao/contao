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
                    ['id' => 4, 'pid' => 1, 'order_value' => 0],
                    ['id' => 3, 'pid' => 1, 'order_value' => 0],
                ],
                [
                    ['id' => 7, 'pid' => 4, 'order_value' => 0],
                    ['id' => 5, 'pid' => 3, 'order_value' => 0],
                ],
                [],
            )
        ;

        $this->assertSame([4, 3, 7, 5], new Hierarchy($connection)->getChildIds(1, 'tl_page'));
    }

    public function testGetsChildIdsIteratively(): void
    {
        $connection = $this->createMock(Connection::class);
        $this->configureConnection($connection);
        $connection
            ->expects($this->exactly(3))
            ->method('fetchAllAssociative')
            ->with($this->callback(static fn (string $sql): bool => str_contains($sql, "ptable = 'tl_page'") && str_contains($sql, 'published = 1') && str_contains($sql, '`position` AS order_value')))
            ->willReturnOnConsecutiveCalls(
                [
                    ['id' => 3, 'pid' => 1, 'order_value' => 20],
                    ['id' => 4, 'pid' => 1, 'order_value' => 10],
                ],
                [
                    ['id' => 5, 'pid' => 3, 'order_value' => 10],
                    ['id' => 7, 'pid' => 4, 'order_value' => 10],
                ],
                [],
            )
        ;

        $query = new ChildQuery()->withOrderBy('position')->withWhere('published = 1');

        $this->assertSame([4, 7, 3, 5], new Hierarchy($connection)->getChildIds(1, 'tl_page', $query));
    }

    public function testGetsParentIdsIteratively(): void
    {
        $connection = $this->createMock(Connection::class);
        $this->configureConnection($connection);
        $connection
            ->expects($this->once())
            ->method('fetchFirstColumn')
            ->with($this->stringContains(' UNION SELECT '), [5])
            ->willReturn([5, 3, 1])
        ;

        $this->assertSame([5, 3, 1], new Hierarchy($connection)->getParentIds(5, 'tl_page'));
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

        $table = new Table('tl_page');
        $table->addColumn('ptable', Types::STRING);

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
