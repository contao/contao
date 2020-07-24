<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Migration\Version409;

use Contao\Config;
use Contao\CoreBundle\Migration\Version410\FolderUrlMigration;
use Contao\CoreBundle\Tests\TestCase;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver\ResultStatement;
use Doctrine\DBAL\Schema\MySqlSchemaManager;

class FolderUrlMigrationTest extends TestCase
{
    public function testDoesNothingIfPageTableDoesNotExist(): void
    {
        $schemaManager = $this->createMock(MySqlSchemaManager::class);
        $schemaManager
            ->expects($this->once())
            ->method('tablesExist')
            ->with('tl_page')
            ->willReturn(false)
        ;

        $connection = $this->createMock(Connection::class);
        $connection
            ->expects($this->once())
            ->method('getSchemaManager')
            ->willReturn($schemaManager)
        ;

        $framework = $this->mockContaoFramework();
        $framework
            ->expects($this->never())
            ->method('initialize')
        ;

        $migration = new FolderUrlMigration($connection, $framework);

        $this->assertFalse($migration->shouldRun());
    }

    public function testDoesNothingIfNoRootPagesExist(): void
    {
        $schemaManager = $this->createMock(MySqlSchemaManager::class);
        $schemaManager
            ->expects($this->once())
            ->method('tablesExist')
            ->with('tl_page')
            ->willReturn(true)
        ;

        $connection = $this->createMock(Connection::class);
        $connection
            ->expects($this->once())
            ->method('getSchemaManager')
            ->willReturn($schemaManager)
        ;

        $connection
            ->expects($this->once())
            ->method('quoteIdentifier')
            ->with('type')
            ->willReturn('`type`')
        ;

        $result = $this->createMock(ResultStatement::class);
        $result
            ->expects($this->once())
            ->method('fetchColumn')
            ->willReturn('0')
        ;

        $connection
            ->expects($this->once())
            ->method('executeQuery')
            ->with("SELECT COUNT(id) FROM tl_page WHERE `type` = 'root'")
            ->willReturn($result)
        ;

        $framework = $this->mockContaoFramework();
        $framework
            ->expects($this->never())
            ->method('initialize')
        ;

        $migration = new FolderUrlMigration($connection, $framework);

        $this->assertFalse($migration->shouldRun());
    }

    public function testDoesNothingIfFolderUrlNotEnabled(): void
    {
        $schemaManager = $this->createMock(MySqlSchemaManager::class);
        $schemaManager
            ->expects($this->once())
            ->method('tablesExist')
            ->with('tl_page')
            ->willReturn(true)
        ;

        $connection = $this->createMock(Connection::class);
        $connection
            ->expects($this->once())
            ->method('getSchemaManager')
            ->willReturn($schemaManager)
        ;

        $connection
            ->expects($this->once())
            ->method('quoteIdentifier')
            ->with('type')
            ->willReturn('`type`')
        ;

        $result = $this->createMock(ResultStatement::class);
        $result
            ->expects($this->once())
            ->method('fetchColumn')
            ->willReturn('1')
        ;

        $connection
            ->expects($this->once())
            ->method('executeQuery')
            ->with("SELECT COUNT(id) FROM tl_page WHERE `type` = 'root'")
            ->willReturn($result)
        ;

        $config = $this->createMock(Config::class);
        $config
            ->expects($this->once())
            ->method('has')
            ->with('folderUrl')
            ->willReturn(false)
        ;

        $framework = $this->mockContaoFramework();

        $framework
            ->expects($this->once())
            ->method('initialize')
        ;

        $framework
            ->expects($this->once())
            ->method('createInstance')
            ->with(Config::class)
            ->willReturn($config)
        ;

        $migration = new FolderUrlMigration($connection, $framework);

        $this->assertFalse($migration->shouldRun());
    }
}
