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
use Doctrine\DBAL\Schema\MySQLSchemaManager;

class TwoFactorBackupCodesMigrationTest extends TestCase
{
    public function testDoesNothingIfTheTablesDoNotExist(): void
    {
        $schemaManager = $this->createMock(MySQLSchemaManager::class);
        $schemaManager
            ->expects($this->once())
            ->method('tablesExist')
            ->with(['tl_user', 'tl_member'])
            ->willReturn(false)
        ;

        $connection = $this->createMock(Connection::class);
        $connection
            ->expects($this->once())
            ->method('createSchemaManager')
            ->willReturn($schemaManager)
        ;

        $migration = new TwoFactorBackupCodesMigration($connection);

        $this->assertFalse($migration->shouldRun());
    }

    public function testDodesNothingIfTheColumnsDoNotExist(): void
    {
        $schemaManager = $this->createMock(MySQLSchemaManager::class);
        $schemaManager
            ->expects($this->once())
            ->method('tablesExist')
            ->with(['tl_user', 'tl_member'])
            ->willReturn(true)
        ;

        $schemaManager
            ->expects($this->exactly(2))
            ->method('listTableColumns')
            ->withConsecutive(['tl_user'], ['tl_member'])
            ->willReturn([])
        ;

        $connection = $this->createMock(Connection::class);
        $connection
            ->expects($this->once())
            ->method('createSchemaManager')
            ->willReturn($schemaManager)
        ;

        $migration = new TwoFactorBackupCodesMigration($connection);

        $this->assertFalse($migration->shouldRun());
    }

    public function testDoesNothingIfThereAreNoRows(): void
    {
        $schemaManager = $this->createMock(MySQLSchemaManager::class);
        $schemaManager
            ->expects($this->once())
            ->method('tablesExist')
            ->with(['tl_user', 'tl_member'])
            ->willReturn(true)
        ;

        $schemaManager
            ->expects($this->exactly(2))
            ->method('listTableColumns')
            ->withConsecutive(['tl_user'], ['tl_member'])
            ->willReturn(['backupcodes' => []])
        ;

        $connection = $this->createMock(Connection::class);
        $connection
            ->expects($this->once())
            ->method('createSchemaManager')
            ->willReturn($schemaManager)
        ;

        $connection
            ->expects($this->exactly(2))
            ->method('fetchAllAssociative')
            ->willReturn([])
        ;

        $migration = new TwoFactorBackupCodesMigration($connection);

        $this->assertFalse($migration->shouldRun());
    }

    public function testUpdatesTheBackupCodes(): void
    {
        $rows = [
            [
                'id' => 1,
                'backupCodes' => '["4ead45-4ea70a"]',
            ],
            [
                'id' => 2,
                'backupCodes' => '["0082ec-b95f03"]',
            ],
        ];

        $schemaManager = $this->createMock(MySQLSchemaManager::class);
        $schemaManager
            ->expects($this->once())
            ->method('tablesExist')
            ->with(['tl_user', 'tl_member'])
            ->willReturn(true)
        ;

        $schemaManager
            ->expects($this->exactly(2))
            ->method('listTableColumns')
            ->withConsecutive(['tl_user'], ['tl_member'])
            ->willReturn(['backupcodes' => []])
        ;

        $connection = $this->createMock(Connection::class);
        $connection
            ->expects($this->once())
            ->method('createSchemaManager')
            ->willReturn($schemaManager)
        ;

        $connection
            ->expects($this->exactly(4))
            ->method('executeStatement')
        ;

        $connection
            ->expects($this->exactly(4))
            ->method('fetchAllAssociative')
            ->willReturn($rows)
        ;

        $migration = new TwoFactorBackupCodesMigration($connection);

        $this->assertTrue($migration->shouldRun());
        $this->assertTrue($migration->run()->isSuccessful());
    }
}
