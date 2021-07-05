<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\NewsBundle\Tests\Migration\Version412;

use Contao\NewsBundle\Migration\Version412\UnifyNewsSourceMigration;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\AbstractSchemaManager;
use Doctrine\DBAL\Schema\Column;
use PHPUnit\Framework\TestCase;

class UnifyNewsSourceMigrationTest extends TestCase
{
    /**
     * @dataProvider providePreconditions
     */
    public function testRunsIfOldFieldsExist(bool $tableExist, array $columns, bool $shouldRun): void
    {
        $schemaManager = $this->createMock(AbstractSchemaManager::class);

        $schemaManager
            ->expects($this->once())
            ->method('tablesExist')
            ->with('tl_news')
            ->willReturn($tableExist)
        ;

        $schemaManager
            ->method('listTableColumns')
            ->with('tl_news')
            ->willReturn($columns)
        ;

        $connection = $this->createMock(Connection::class);
        $connection
            ->method('getSchemaManager')
            ->willReturn($schemaManager)
        ;

        $this->assertSame($shouldRun, (new UnifyNewsSourceMigration($connection))->shouldRun());
    }

    public function providePreconditions(): \Generator
    {
        yield 'tl_news does not exist' => [
            false, [], false,
        ];

        $column = $this->createMock(Column::class);

        yield 'already migrated (no articleid/jumpto columns)' => [
            true, ['id' => $column, 'foo' => $column], false,
        ];

        yield 'should migrate' => [
            true, ['id' => $column, 'articleid' => $column, 'jumpto' => $column], true,
        ];
    }

    /**
     * @dataProvider provideTransformations
     */
    public function testMigratesAndDropsColumns(array $from, array $expectedUpdate): void
    {
        [$source, $jumpTo, $articleId] = $from;

        $connection = $this->createMock(Connection::class);

        $connection
            ->method('fetchAllAssociative')
            ->with("SELECT id, source, articleId, jumpTo FROM tl_news WHERE source IN ('internal', 'article')")
            ->willReturn([
                [
                    'id' => 123,
                    'source' => $source,
                    'articleId' => $articleId,
                    'jumpTo' => $jumpTo,
                ],
            ])
        ;

        $connection
            ->expects($this->once())
            ->method('update')
            ->with('tl_news', $expectedUpdate, ['id' => 123])
        ;

        $connection
            ->expects($this->once())
            ->method('executeQuery')
            ->with('ALTER TABLE tl_news DROP COLUMN articleId, DROP COLUMN jumpTo')
        ;

        (new UnifyNewsSourceMigration($connection))->run();
    }

    public function provideTransformations(): \Generator
    {
        yield 'transform "internal"' => [
            ['internal', '5', '0'],
            ['source' => 'external', 'url' => '{{link_url::5}}', 'target' => ''],
        ];

        yield 'transform "article"' => [
            ['article', '0', '3'],
            ['source' => 'external', 'url' => '{{article_url::3}}', 'target' => ''],
        ];
    }
}
