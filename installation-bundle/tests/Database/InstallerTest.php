<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\InstallationBundle\Tests\Database;

use Contao\CoreBundle\Doctrine\Schema\DcaSchemaProvider;
use Contao\InstallationBundle\Database\Installer;
use Doctrine\DBAL\Configuration;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Platforms\MySqlPlatform;
use Doctrine\DBAL\Schema\MySqlSchemaManager;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Statement;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class InstallerTest extends TestCase
{
    public function testCanBeInstantiated(): void
    {
        $installer = $this->mockInstaller();

        $this->assertInstanceOf('Contao\InstallationBundle\Database\Installer', $installer);
    }

    public function testReturnsTheAlterTableCommands(): void
    {
        $fromSchema = new Schema();
        $fromSchema
            ->createTable('tl_foo')
            ->addColumn('foo', 'string')
        ;

        $toSchema = new Schema();
        $toSchema
            ->createTable('tl_foo')
            ->addOption('engine', 'InnoDB')
            ->addOption('charset', 'utf8mb4')
            ->addOption('collate', 'utf8mb4_unicode_ci')
            ->addColumn('foo', 'string')
        ;

        $installer = $this->mockInstaller($fromSchema, $toSchema, ['tl_foo']);
        $commands = $installer->getCommands();

        $this->assertArrayHasKey('ALTER_TABLE', $commands);
        $this->assertArrayHasKey('d21451588bc7442c256f8a0be02c3430', $commands['ALTER_TABLE']);
        $this->assertArrayHasKey('fb9f8dee53c39b7be92194908d98731e', $commands['ALTER_TABLE']);

        $this->assertSame(
            'ALTER TABLE tl_foo ENGINE = InnoDB ROW_FORMAT = DYNAMIC',
            $commands['ALTER_TABLE']['d21451588bc7442c256f8a0be02c3430']
        );

        $this->assertSame(
            'ALTER TABLE tl_foo CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci',
            $commands['ALTER_TABLE']['fb9f8dee53c39b7be92194908d98731e']
        );
    }

    public function testDeletesTheIndexesWhenChangingTheDatabaseEngine(): void
    {
        $fromSchema = new Schema();
        $fromSchema
            ->createTable('tl_foo')
            ->addOption('engine', 'MyISAM')
        ;

        $fromSchema
            ->getTable('tl_foo')
            ->addColumn('foo', 'string')
        ;

        $fromSchema
            ->getTable('tl_foo')
            ->addIndex(['foo'], 'foo_idx')
        ;

        $toSchema = new Schema();
        $toSchema
            ->createTable('tl_foo')
            ->addOption('engine', 'InnoDB')
        ;

        $toSchema
            ->getTable('tl_foo')
            ->addColumn('foo', 'string')
        ;

        $toSchema
            ->getTable('tl_foo')
            ->addIndex(['foo'], 'foo_idx')
        ;

        $installer = $this->mockInstaller($fromSchema, $toSchema, ['tl_foo']);
        $commands = $installer->getCommands();

        $this->assertSame(
            'DROP INDEX foo_idx ON tl_foo',
            $commands['ALTER_TABLE']['db24ce0a48761ea6f77d644a422a3fe0']
        );
    }

    public function testDeletesTheIndexesWhenChangingTheCollation(): void
    {
        $fromSchema = new Schema();
        $fromSchema
            ->createTable('tl_foo')
            ->addOption('collate', 'utf8_unicode_ci')
        ;

        $fromSchema
            ->getTable('tl_foo')
            ->addColumn('foo', 'string')
        ;

        $fromSchema
            ->getTable('tl_foo')
            ->addIndex(['foo'], 'foo_idx')
        ;

        $toSchema = new Schema();
        $toSchema
            ->createTable('tl_foo')
            ->addOption('collate', 'utf8mb4_unicode_ci')
        ;

        $toSchema
            ->getTable('tl_foo')
            ->addColumn('foo', 'string')
        ;

        $toSchema
            ->getTable('tl_foo')
            ->addIndex(['foo'], 'foo_idx')
        ;

        $installer = $this->mockInstaller($fromSchema, $toSchema, ['tl_foo']);
        $commands = $installer->getCommands();

        $this->assertSame(
            'DROP INDEX foo_idx ON tl_foo',
            $commands['ALTER_TABLE']['db24ce0a48761ea6f77d644a422a3fe0']
        );
    }

    public function testChangesTheRowFormatIfInnodbIsUsed(): void
    {
        $fromSchema = new Schema();
        $fromSchema
            ->createTable('tl_bar')
            ->addColumn('foo', 'string')
        ;

        $toSchema = new Schema();
        $toSchema
            ->createTable('tl_bar')
            ->addOption('engine', 'InnoDB')
            ->addOption('row_format', 'DYNAMIC')
            ->addOption('charset', 'utf8mb4')
            ->addOption('collate', 'utf8mb4_unicode_ci')
            ->addColumn('foo', 'string')
        ;

        $installer = $this->mockInstaller($fromSchema, $toSchema, ['tl_foo']);
        $commands = $installer->getCommands();

        $this->assertArrayHasKey('ALTER_TABLE', $commands);
        $this->assertArrayHasKey('754c11ae50c43c54456fcd31da3baccb', $commands['ALTER_TABLE']);

        $this->assertSame(
            'ALTER TABLE tl_bar ENGINE = InnoDB ROW_FORMAT = DYNAMIC',
            $commands['ALTER_TABLE']['754c11ae50c43c54456fcd31da3baccb']
        );
    }

    public function testDoesNotChangeTheRowFormatIfDynamicRowsAreNotSupported(): void
    {
        $fromSchema = new Schema();
        $fromSchema
            ->createTable('tl_foo')
            ->addColumn('foo', 'string')
        ;

        $toSchema = new Schema();
        $toSchema
            ->createTable('tl_foo')
            ->addOption('engine', 'InnoDB')
            ->addOption('row_format', 'DYNAMIC')
            ->addOption('charset', 'utf8mb4')
            ->addOption('collate', 'utf8mb4_unicode_ci')
            ->addColumn('foo', 'string')
        ;

        $installer = $this->mockInstaller($fromSchema, $toSchema, ['tl_foo'], 'OFF');
        $commands = $installer->getCommands();

        $this->assertArrayHasKey('ALTER_TABLE', $commands);
        $this->assertArrayHasKey('537747ae8a3a53e6277dfccf354bc7da', $commands['ALTER_TABLE']);

        $this->assertSame(
            'ALTER TABLE tl_foo ENGINE = InnoDB',
            $commands['ALTER_TABLE']['537747ae8a3a53e6277dfccf354bc7da']
        );
    }

    public function testReturnsTheDropColumnCommands(): void
    {
        $fromSchema = new Schema();
        $fromSchema
            ->createTable('tl_foo')
            ->addColumn('foo', 'string')
        ;

        $fromSchema
            ->getTable('tl_foo')
            ->addColumn('bar', 'string')
        ;

        $toSchema = new Schema();
        $toSchema
            ->createTable('tl_foo')
            ->addColumn('foo', 'string')
        ;

        $installer = $this->mockInstaller($fromSchema, $toSchema);
        $commands = $installer->getCommands();

        $this->assertArrayHasKey('ALTER_DROP', $commands);
        $this->assertSame('ALTER TABLE tl_foo DROP bar', reset($commands['ALTER_DROP']));
    }

    public function testReturnsTheAddColumnCommands(): void
    {
        $fromSchema = new Schema();
        $fromSchema
            ->createTable('tl_foo')
            ->addColumn('foo', 'string')
        ;

        $toSchema = new Schema();
        $toSchema
            ->createTable('tl_foo')
            ->addColumn('foo', 'string')
        ;

        $toSchema
            ->getTable('tl_foo')
            ->addColumn('bar', 'string')
        ;

        $installer = $this->mockInstaller($fromSchema, $toSchema);
        $commands = $installer->getCommands();

        $this->assertArrayHasKey('ALTER_ADD', $commands);

        $commands = array_values($commands['ALTER_ADD']);

        $this->assertSame('ALTER TABLE tl_foo ADD bar VARCHAR(255) NOT NULL', $commands[0]);
    }

    public function testHandlesDecimalsInTheAddColumnCommands(): void
    {
        $fromSchema = new Schema();
        $fromSchema->createTable('tl_foo');

        $toSchema = new Schema();
        $toSchema
            ->createTable('tl_foo')
            ->addColumn('foo', 'decimal', ['precision' => 9, 'scale' => 2])
        ;

        $installer = $this->mockInstaller($fromSchema, $toSchema);
        $commands = $installer->getCommands();

        $this->assertArrayHasKey('ALTER_ADD', $commands);

        $commands = array_values($commands['ALTER_ADD']);

        $this->assertSame('ALTER TABLE tl_foo ADD foo NUMERIC(9,2) NOT NULL', $commands[0]);
    }

    public function testHandlesDefaultsInTheAddColumnCommands(): void
    {
        $fromSchema = new Schema();
        $fromSchema->createTable('tl_foo');

        $toSchema = new Schema();
        $toSchema
            ->createTable('tl_foo')
            ->addColumn('foo', 'string', ['default' => ','])
        ;

        $installer = $this->mockInstaller($fromSchema, $toSchema);
        $commands = $installer->getCommands();

        $this->assertArrayHasKey('ALTER_ADD', $commands);

        $commands = array_values($commands['ALTER_ADD']);

        $this->assertSame("ALTER TABLE tl_foo ADD foo VARCHAR(255) DEFAULT ',' NOT NULL", $commands[0]);
    }

    public function testHandlesMixedColumnsInTheAddColumnCommands(): void
    {
        $fromSchema = new Schema();
        $fromSchema->createTable('tl_foo');

        $toSchema = new Schema();
        $toSchema
            ->createTable('tl_foo')
            ->addColumn('foo1', 'string')
        ;

        $toSchema
            ->getTable('tl_foo')
            ->addColumn('foo2', 'integer')
        ;

        $toSchema
            ->getTable('tl_foo')
            ->addColumn('foo3', 'decimal', ['precision' => 9, 'scale' => 2])
        ;

        $toSchema
            ->getTable('tl_foo')
            ->addColumn('foo4', 'string', ['default' => ','])
        ;

        $installer = $this->mockInstaller($fromSchema, $toSchema);
        $commands = $installer->getCommands();

        $this->assertArrayHasKey('ALTER_ADD', $commands);

        $commands = array_values($commands['ALTER_ADD']);

        $this->assertCount(4, $commands);
        $this->assertContains('ALTER TABLE tl_foo ADD foo1 VARCHAR(255) NOT NULL', $commands);
        $this->assertContains('ALTER TABLE tl_foo ADD foo2 INT NOT NULL', $commands);
        $this->assertContains('ALTER TABLE tl_foo ADD foo3 NUMERIC(9,2) NOT NULL', $commands);
        $this->assertContains("ALTER TABLE tl_foo ADD foo4 VARCHAR(255) DEFAULT ',' NOT NULL", $commands);
    }

    public function testReturnsNoCommandsIfTheSchemasAreIdentical(): void
    {
        $fromSchema = new Schema();
        $fromSchema
            ->createTable('tl_foo')
            ->addColumn('foo', 'string')
        ;

        $toSchema = new Schema();
        $toSchema
            ->createTable('tl_foo')
            ->addOption('engine', 'MyISAM')
            ->addOption('charset', 'utf8')
            ->addOption('collate', 'utf8_unicode_ci')
            ->addColumn('foo', 'string')
        ;

        $installer = $this->mockInstaller($fromSchema, $toSchema);
        $commands = $installer->getCommands();

        $this->assertEmpty($commands);
    }

    /**
     * Mocks an installer.
     *
     * @return Installer|MockObject
     */
    private function mockInstaller(Schema $fromSchema = null, Schema $toSchema = null, array $tables = [], string $filePerTable = 'ON'): Installer
    {
        $schemaManager = $this->createMock(MySqlSchemaManager::class);
        $schemaManager
            ->method('createSchema')
            ->willReturn($fromSchema)
        ;

        $schemaManager
            ->method('listTableNames')
            ->willReturn($tables)
        ;

        $connection = $this->createMock(Connection::class);
        $connection
            ->method('getSchemaManager')
            ->willReturn($schemaManager)
        ;

        $connection
            ->method('getDatabasePlatform')
            ->willReturn(new MySqlPlatform())
        ;

        $connection
            ->method('query')
            ->willReturnCallback(
                function (string $query) use ($filePerTable): ?MockObject {
                    switch ($query) {
                        case "SHOW VARIABLES LIKE 'innodb_file_per_table'":
                            $statement = $this->createMock(Statement::class);
                            $statement
                                ->method('fetch')
                                ->willReturn((object) ['Value' => $filePerTable])
                            ;

                            return $statement;

                        case "SHOW VARIABLES LIKE 'innodb_file_format'":
                            $statement = $this->createMock(Statement::class);
                            $statement
                                ->method('fetch')
                                ->willReturn((object) ['Value' => 'Barracuda'])
                            ;

                            return $statement;

                        case "SHOW TABLE STATUS LIKE 'tl_foo'":
                            $statement = $this->createMock(Statement::class);
                            $statement
                                ->method('fetch')
                                ->willReturn((object) [
                                    'Engine' => 'MyISAM',
                                    'Collation' => 'utf8_unicode_ci',
                                ])
                            ;

                            return $statement;

                        case "SHOW TABLE STATUS LIKE 'tl_bar'":
                            $statement = $this->createMock(Statement::class);
                            $statement
                                ->method('fetch')
                                ->willReturn((object) [
                                    'Engine' => 'InnoDB',
                                    'Row_format' => 'COMPATCT',
                                    'Collation' => 'utf8mb4_unicode_ci',
                                ])
                            ;

                            return $statement;
                    }

                    return null;
                }
            )
        ;

        $connection
            ->method('getConfiguration')
            ->willReturn($this->createMock(Configuration::class))
        ;

        $schemaProvider = $this->createMock(DcaSchemaProvider::class);
        $schemaProvider
            ->method('createSchema')
            ->willReturn($toSchema)
        ;

        return new Installer($connection, $schemaProvider);
    }
}
