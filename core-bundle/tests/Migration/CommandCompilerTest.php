<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Migration;

use Contao\CoreBundle\Doctrine\Schema\SchemaProvider;
use Contao\CoreBundle\Migration\CommandCompiler;
use Doctrine\DBAL\Configuration;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Platforms\MySQLPlatform;
use Doctrine\DBAL\Schema\MySQLSchemaManager;
use Doctrine\DBAL\Schema\Schema;
use PHPUnit\Framework\TestCase;

class CommandCompilerTest extends TestCase
{
    public function testReturnsTheAlterTableCommands(): void
    {
        $fromSchema = new Schema();
        $fromSchema
            ->createTable('tl_foo')
            ->addOption('engine', 'MyISAM')
            ->addOption('charset', 'utf8')
            ->addOption('collate', 'utf8_unicode_ci')
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

        $installer = $this->getInstaller($fromSchema, $toSchema, ['tl_foo']);
        $commands = $installer->compileCommands();

        $this->assertContains(
            'ALTER TABLE tl_foo ENGINE = InnoDB ROW_FORMAT = DYNAMIC',
            $commands,
        );

        $this->assertContains(
            'ALTER TABLE tl_foo CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci',
            $commands,
        );
    }

    public function testChangesTheDatabaseEngine(): void
    {
        $fromSchema = new Schema();
        $fromSchema
            ->createTable('tl_foo')
            ->addOption('engine', 'MyISAM')
            ->addOption('charset', 'utf8')
            ->addOption('collate', 'utf8_unicode_ci')
        ;

        $fromSchema->getTable('tl_foo')->addColumn('foo', 'string');
        $fromSchema->getTable('tl_foo')->addIndex(['foo'], 'foo_idx');

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

        $installer = $this->getInstaller($fromSchema, $toSchema, ['tl_foo']);
        $commands = $installer->compileCommands();

        $this->assertContains('ALTER TABLE tl_foo ENGINE = InnoDB ROW_FORMAT = DYNAMIC', $commands);
    }

    public function testResetsTheKeyBlockSizeWhenChangingTheDatabaseEngine(): void
    {
        $fromSchema = new Schema();
        $fromSchema
            ->createTable('tl_foo')
            ->addOption('engine', 'MyISAM')
            ->addOption('create_options', ['KEY_BLOCK_SIZE=16'])
            ->addOption('charset', 'utf8')
            ->addOption('collate', 'utf8_unicode_ci')
        ;

        $fromSchema->getTable('tl_foo')->addColumn('foo', 'string');
        $fromSchema->getTable('tl_foo')->addIndex(['foo'], 'foo_idx');

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

        $installer = $this->getInstaller($fromSchema, $toSchema, ['tl_foo']);
        $commands = $installer->compileCommands();

        $this->assertContains('ALTER TABLE tl_foo ENGINE = InnoDB ROW_FORMAT = DYNAMIC KEY_BLOCK_SIZE = 0', $commands);
    }

    public function testDeletesTheIndexesWhenChangingTheDatabaseEngine(): void
    {
        $fromSchema = new Schema();
        $fromSchema
            ->createTable('tl_foo')
            ->addOption('engine', 'MyISAM')
            ->addOption('charset', 'utf8')
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

        $installer = $this->getInstaller($fromSchema, $toSchema, ['tl_foo']);
        $commands = $installer->compileCommands();

        $this->assertContains('DROP INDEX foo_idx ON tl_foo', $commands);
    }

    public function testDeletesTheIndexesWhenChangingTheCollation(): void
    {
        $fromSchema = new Schema();
        $fromSchema
            ->createTable('tl_foo')
            ->addOption('engine', 'MyISAM')
            ->addOption('charset', 'utf8')
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

        $installer = $this->getInstaller($fromSchema, $toSchema, ['tl_foo']);
        $commands = $installer->compileCommands();

        $this->assertContains('DROP INDEX foo_idx ON tl_foo', $commands);
    }

    public function testChangesTheRowFormatIfInnodbIsUsed(): void
    {
        $fromSchema = new Schema();
        $fromSchema
            ->createTable('tl_bar')
            ->addOption('engine', 'InnoDB')
            ->addOption('row_format', 'COMPACT')
            ->addOption('charset', 'utf8mb4')
            ->addOption('collate', 'utf8mb4_unicode_ci')
            ->addOption('Create_options', 'row_format=COMPACT')
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

        $installer = $this->getInstaller($fromSchema, $toSchema, ['tl_foo']);
        $commands = $installer->compileCommands();

        $this->assertContains('ALTER TABLE tl_bar ENGINE = InnoDB ROW_FORMAT = DYNAMIC', $commands);
    }

    public function testResetsTheKeyBlockSizeIfInnodbIsUsed(): void
    {
        $fromSchema = new Schema();
        $fromSchema
            ->createTable('tl_bar')
            ->addOption('engine', 'InnoDB')
            ->addOption('charset', 'utf8mb4')
            ->addOption('collate', 'utf8mb4_unicode_ci')
            ->addOption('create_options', ['row_format=COMPACT', 'KEY_BLOCK_SIZE=16'])
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

        $installer = $this->getInstaller($fromSchema, $toSchema, ['tl_foo']);
        $commands = $installer->compileCommands();

        $this->assertContains('ALTER TABLE tl_bar ENGINE = InnoDB ROW_FORMAT = DYNAMIC KEY_BLOCK_SIZE = 0', $commands);
    }

    public function testDoesNotChangeTheRowFormatIfDynamicRowsAreNotSupported(): void
    {
        $fromSchema = new Schema();
        $fromSchema
            ->createTable('tl_foo')
            ->addOption('engine', 'MyISAM')
            ->addOption('charset', 'utf8')
            ->addOption('collate', 'utf8_unicode_ci')
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

        $installer = $this->getInstaller($fromSchema, $toSchema, ['tl_foo'], 'OFF');
        $commands = $installer->compileCommands();

        $this->assertContains('ALTER TABLE tl_foo ENGINE = InnoDB', $commands);
    }

    public function testDoesNotChangeTheRowFormatIfTableOptionsAreNotAvailable(): void
    {
        $fromSchema = new Schema();
        $fromSchema
            ->createTable('tl_foo_view')
            ->addColumn('foo', 'string')
        ;

        $toSchema = new Schema();
        $toSchema
            ->createTable('tl_foo_view')
            ->addOption('engine', 'InnoDB')
            ->addOption('row_format', 'DYNAMIC')
            ->addOption('charset', 'utf8mb4')
            ->addOption('collate', 'utf8mb4_unicode_ci')
            ->addColumn('foo', 'string')
        ;

        $installer = $this->getInstaller($fromSchema, $toSchema, ['tl_foo_view']);

        $this->assertEmpty($installer->compileCommands());
    }

    public function testReturnsTheDropColumnCommands(): void
    {
        $fromSchema = new Schema();
        $fromSchema
            ->createTable('tl_foo')
            ->addOption('engine', 'MyISAM')
            ->addOption('charset', 'utf8')
            ->addOption('collate', 'utf8_unicode_ci')
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

        $installer = $this->getInstaller($fromSchema, $toSchema);
        $commands = $installer->compileCommands();

        $this->assertContains('ALTER TABLE tl_foo DROP bar', $commands);
    }

    public function testReturnsTheAddColumnCommands(): void
    {
        $fromSchema = new Schema();
        $fromSchema
            ->createTable('tl_foo')
            ->addOption('engine', 'MyISAM')
            ->addOption('charset', 'utf8')
            ->addOption('collate', 'utf8_unicode_ci')
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

        $installer = $this->getInstaller($fromSchema, $toSchema);
        $commands = $installer->compileCommands();

        $this->assertContains('ALTER TABLE tl_foo ADD bar VARCHAR(255) NOT NULL', $commands);
    }

    public function testHandlesDecimalsInTheAddColumnCommands(): void
    {
        $fromSchema = new Schema();
        $fromSchema
            ->createTable('tl_foo')
            ->addOption('engine', 'MyISAM')
            ->addOption('charset', 'utf8')
            ->addOption('collate', 'utf8_unicode_ci')
        ;

        $toSchema = new Schema();
        $toSchema
            ->createTable('tl_foo')
            ->addColumn('foo', 'decimal', ['precision' => 9, 'scale' => 2])
        ;

        $installer = $this->getInstaller($fromSchema, $toSchema);
        $commands = $installer->compileCommands();

        $this->assertContains('ALTER TABLE tl_foo ADD foo NUMERIC(9, 2) NOT NULL', $commands);
    }

    public function testHandlesDefaultsInTheAddColumnCommands(): void
    {
        $fromSchema = new Schema();
        $fromSchema
            ->createTable('tl_foo')
            ->addOption('engine', 'MyISAM')
            ->addOption('charset', 'utf8')
            ->addOption('collate', 'utf8_unicode_ci')
        ;

        $toSchema = new Schema();
        $toSchema
            ->createTable('tl_foo')
            ->addColumn('foo', 'string', ['default' => ','])
        ;

        $installer = $this->getInstaller($fromSchema, $toSchema);
        $commands = $installer->compileCommands();

        $this->assertContains("ALTER TABLE tl_foo ADD foo VARCHAR(255) DEFAULT ',' NOT NULL", $commands);
    }

    public function testHandlesMixedColumnsInTheAddColumnCommands(): void
    {
        $fromSchema = new Schema();
        $fromSchema
            ->createTable('tl_foo')
            ->addOption('engine', 'MyISAM')
            ->addOption('charset', 'utf8')
            ->addOption('collate', 'utf8_unicode_ci')
        ;

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

        $installer = $this->getInstaller($fromSchema, $toSchema);
        $commands = $installer->compileCommands();

        $this->assertContains(
            'ALTER TABLE tl_foo '.
                'ADD foo1 VARCHAR(255) NOT NULL, '.
                'ADD foo2 INT NOT NULL, '.
                'ADD foo3 NUMERIC(9, 2) NOT NULL, '.
                "ADD foo4 VARCHAR(255) DEFAULT ',' NOT NULL",
            $commands,
        );
    }

    public function testReturnsNoCommandsIfTheSchemasAreIdentical(): void
    {
        $fromSchema = new Schema();
        $fromSchema
            ->createTable('tl_foo')
            ->addOption('engine', 'MyISAM')
            ->addOption('charset', 'utf8')
            ->addOption('collate', 'utf8_unicode_ci')
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

        $installer = $this->getInstaller($fromSchema, $toSchema);
        $commands = $installer->compileCommands();

        $this->assertEmpty($commands);
    }

    private function getInstaller(Schema|null $fromSchema = null, Schema|null $toSchema = null, array $tables = [], string $filePerTable = 'ON'): CommandCompiler
    {
        $platform = new MySQLPlatform();
        $comparator = (new MySQLSchemaManager($this->createMock(Connection::class), $platform))->createComparator();

        $schemaManager = $this->createMock(MySQLSchemaManager::class);
        $schemaManager
            ->method('introspectSchema')
            ->willReturn($fromSchema)
        ;

        $schemaManager
            ->method('createComparator')
            ->willReturn($comparator)
        ;

        $schemaManager
            ->method('listTableNames')
            ->willReturn($tables)
        ;

        $connection = $this->createMock(Connection::class);
        $connection
            ->method('createSchemaManager')
            ->willReturn($schemaManager)
        ;

        $connection
            ->method('getDatabasePlatform')
            ->willReturn($platform)
        ;

        $connection
            ->method('fetchAssociative')
            ->willReturnCallback(
                static function (string $query, array $parameters) use ($filePerTable, $fromSchema) {
                    switch ($query) {
                        case "SHOW VARIABLES LIKE 'innodb_file_per_table'":
                            return ['Value' => $filePerTable];

                        case "SHOW VARIABLES LIKE 'innodb_file_format'":
                            return ['Value' => 'Barracuda'];

                        case 'SHOW TABLE STATUS WHERE Name = ? AND Engine IS NOT NULL AND Create_options IS NOT NULL AND Collation IS NOT NULL':
                            $table = $fromSchema->getTable($parameters[0]);

                            if ($table->hasOption('engine')) {
                                return [
                                    'Engine' => $table->getOption('engine'),
                                    'Row_format' => $table->hasOption('row_format') ? $table->getOption('row_format') : '',
                                    'Create_options' => implode(', ', $table->getOption('create_options')),
                                    'Collation' => $table->hasOption('collate') ? $table->getOption('collate') : '',
                                ];
                            }

                            return false;
                    }

                    return null;
                },
            )
        ;

        $connection
            ->method('getConfiguration')
            ->willReturn($this->createMock(Configuration::class))
        ;

        $schemaProvider = $this->createMock(SchemaProvider::class);
        $schemaProvider
            ->method('createSchema')
            ->willReturn($toSchema)
        ;

        return new CommandCompiler($connection, $schemaProvider);
    }
}
