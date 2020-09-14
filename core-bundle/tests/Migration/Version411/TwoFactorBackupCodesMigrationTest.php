<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Migration\Version411;

use Contao\CoreBundle\Migration\Version411\TwoFactorBackupCodesMigration;
use Contao\CoreBundle\Tests\TestCase;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\MySqlSchemaManager;
use Doctrine\DBAL\Statement;

class TwoFactorBackupCodesMigrationTest extends TestCase
{
    public function testDoesNothingIfTheTablesDoNotExist(): void
    {
        $schemaManager = $this->createMock(MySqlSchemaManager::class);
        $schemaManager
            ->expects($this->once())
            ->method('tablesExist')
            ->with(['tl_user', 'tl_member'])
            ->willReturn(false)
        ;

        $connection = $this->createMock(Connection::class);
        $connection
            ->expects($this->once())
            ->method('getSchemaManager')
            ->willReturn($schemaManager)
        ;

        $migration = new TwoFactorBackupCodesMigration($connection);

        $this->assertFalse($migration->shouldRun());
    }

    public function testDoesNothingIfNoRowsExist(): void
    {
        $schemaManager = $this->createMock(MySqlSchemaManager::class);
        $schemaManager
            ->expects($this->once())
            ->method('tablesExist')
            ->with(['tl_user', 'tl_member'])
            ->willReturn(true)
        ;

        $statement = $this->createMock(Statement::class);
        $statement
            ->expects($this->exactly(2))
            ->method('execute')
        ;

        $statement
            ->expects($this->exactly(2))
            ->method('fetchAll')
            ->willReturn([])
        ;

        $connection = $this->createMock(Connection::class);
        $connection
            ->expects($this->once())
            ->method('getSchemaManager')
            ->willReturn($schemaManager)
        ;

        $connection
            ->expects($this->exactly(2))
            ->method('prepare')
            ->willReturn($statement)
        ;

        $migration = new TwoFactorBackupCodesMigration($connection);

        $this->assertFalse($migration->shouldRun());
    }

    public function testUpdatesTheBackupCodes(): void
    {
        $rows = [
            [
                'id' => 1,
                'backupCodes' => '[]',
            ],
            [
                'id' => 2,
                'backupCodes' => '[]',
            ],
        ];

        $schemaManager = $this->createMock(MySqlSchemaManager::class);
        $schemaManager
            ->expects($this->once())
            ->method('tablesExist')
            ->with(['tl_user', 'tl_member'])
            ->willReturn(true)
        ;

        $statement = $this->createMock(Statement::class);
        $statement
            ->expects($this->exactly(8))
            ->method('execute')
        ;

        $statement
            ->expects($this->exactly(4))
            ->method('fetchAll')
            ->willReturn($rows)
        ;

        $connection = $this->createMock(Connection::class);
        $connection
            ->expects($this->once())
            ->method('getSchemaManager')
            ->willReturn($schemaManager)
        ;

        $connection
            ->expects($this->exactly(8))
            ->method('prepare')
            ->willReturn($statement)
        ;

        $migration = new TwoFactorBackupCodesMigration($connection);

        $this->assertTrue($migration->shouldRun());
        $this->assertTrue($migration->run()->isSuccessful());
    }
}
