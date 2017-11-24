<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2017 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\InstallationBundle\Test\Database;

use Contao\CoreBundle\Doctrine\Schema\DcaSchemaProvider;
use Contao\InstallationBundle\Database\Installer;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Platforms\MySqlPlatform;
use Doctrine\DBAL\Schema\MySqlSchemaManager;
use Doctrine\DBAL\Schema\Schema;
use PHPUnit\Framework\TestCase;

class InstallerTest extends TestCase
{
    public function testCanBeInstantiated(): void
    {
        $installer = $this->mockInstaller();

        $this->assertInstanceOf('Contao\InstallationBundle\Database\Installer', $installer);
    }

    public function testReturnsTheAlterTableDropCommand(): void
    {
        $fromSchema = new Schema();
        $fromSchema->createTable('tl_foobar')->addColumn('foo', 'string');
        $fromSchema->getTable('tl_foobar')->addColumn('bar', 'string');

        $toSchema = new Schema();
        $toSchema->createTable('tl_foobar')->addColumn('foo', 'string');

        $installer = $this->mockInstaller($fromSchema, $toSchema);
        $commands = $installer->getCommands();

        $this->assertArrayHasKey('ALTER_DROP', $commands);
        $this->assertSame('ALTER TABLE tl_foobar DROP bar', reset($commands['ALTER_DROP']));
    }

    public function testReturnsTheAlterTableAddCommand(): void
    {
        $fromSchema = new Schema();
        $fromSchema->createTable('tl_foobar')->addColumn('foo', 'string');

        $toSchema = new Schema();
        $toSchema->createTable('tl_foobar')->addColumn('foo', 'string');
        $toSchema->getTable('tl_foobar')->addColumn('bar', 'string');

        $installer = $this->mockInstaller($fromSchema, $toSchema);
        $commands = $installer->getCommands();

        $this->assertArrayHasKey('ALTER_ADD', $commands);

        $commands = array_values($commands['ALTER_ADD']);

        $this->assertSame('ALTER TABLE tl_foobar ADD bar VARCHAR(255) NOT NULL', $commands[0]);
    }

    public function testHandlesDecimalsInTheAlterTableDropCommand(): void
    {
        $fromSchema = new Schema();
        $fromSchema->createTable('tl_foobar');

        $toSchema = new Schema();
        $toSchema->createTable('tl_foobar')->addColumn('foo', 'decimal', ['precision' => 9, 'scale' => 2]);

        $installer = $this->mockInstaller($fromSchema, $toSchema);
        $commands = $installer->getCommands();

        $this->assertArrayHasKey('ALTER_ADD', $commands);

        $commands = array_values($commands['ALTER_ADD']);

        $this->assertSame('ALTER TABLE tl_foobar ADD foo NUMERIC(9,2) NOT NULL', $commands[0]);
    }

    public function testHandlesDefaultsInTheAlterTableDropCommand(): void
    {
        $fromSchema = new Schema();
        $fromSchema->createTable('tl_foobar');

        $toSchema = new Schema();
        $toSchema->createTable('tl_foobar')->addColumn('foo', 'string', ['default' => ',']);

        $installer = $this->mockInstaller($fromSchema, $toSchema);
        $commands = $installer->getCommands();

        $this->assertArrayHasKey('ALTER_ADD', $commands);

        $commands = array_values($commands['ALTER_ADD']);

        $this->assertSame("ALTER TABLE tl_foobar ADD foo VARCHAR(255) DEFAULT ',' NOT NULL", $commands[0]);
    }

    public function testHandlesMixedColumnsInTheAlterTableDropCommand(): void
    {
        $fromSchema = new Schema();
        $fromSchema->createTable('tl_foobar');

        $toSchema = new Schema();
        $toSchema->createTable('tl_foobar')->addColumn('foo1', 'string');
        $toSchema->getTable('tl_foobar')->addColumn('foo2', 'integer');
        $toSchema->getTable('tl_foobar')->addColumn('foo3', 'decimal', ['precision' => 9, 'scale' => 2]);
        $toSchema->getTable('tl_foobar')->addColumn('foo4', 'string', ['default' => ',']);

        $installer = $this->mockInstaller($fromSchema, $toSchema);
        $commands = $installer->getCommands();

        $this->assertArrayHasKey('ALTER_ADD', $commands);

        $commands = array_values($commands['ALTER_ADD']);

        $this->assertCount(4, $commands);
        $this->assertContains('ALTER TABLE tl_foobar ADD foo1 VARCHAR(255) NOT NULL', $commands);
        $this->assertContains('ALTER TABLE tl_foobar ADD foo2 INT NOT NULL', $commands);
        $this->assertContains('ALTER TABLE tl_foobar ADD foo3 NUMERIC(9,2) NOT NULL', $commands);
        $this->assertContains("ALTER TABLE tl_foobar ADD foo4 VARCHAR(255) DEFAULT ',' NOT NULL", $commands);
    }

    public function testReturnsNoCommandsIfTheSchemasAreIdentical(): void
    {
        $fromSchema = new Schema();
        $fromSchema->createTable('tl_foobar')->addColumn('foo', 'string');

        $toSchema = new Schema();
        $toSchema->createTable('tl_foobar')->addColumn('foo', 'string');

        $installer = $this->mockInstaller($fromSchema, $toSchema);
        $commands = $installer->getCommands();

        $this->assertEmpty($commands);
    }

    /**
     * Mocks an installer.
     *
     * @param Schema|null $fromSchema
     * @param Schema|null $toSchema
     *
     * @return Installer|\PHPUnit_Framework_MockObject_MockObject
     */
    private function mockInstaller(Schema $fromSchema = null, Schema $toSchema = null): Installer
    {
        $schemaManager = $this->createMock(MySqlSchemaManager::class);

        $schemaManager
            ->method('createSchema')
            ->willReturn($fromSchema)
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

        $schemaProvider = $this->createMock(DcaSchemaProvider::class);

        $schemaProvider
            ->method('createSchema')
            ->willReturn($toSchema)
        ;

        return new Installer($connection, $schemaProvider);
    }
}
