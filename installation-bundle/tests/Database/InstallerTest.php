<?php

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
use Doctrine\DBAL\Schema\AbstractSchemaManager;
use Doctrine\DBAL\Schema\Schema;

class InstallerTest extends \PHPUnit_Framework_TestCase
{

    /**
     * Tests the object instantiation.
     */
    public function testInstantiation()
    {
        $installer = $this->createInstaller();

        $this->assertInstanceOf('Contao\InstallationBundle\Database\Installer', $installer);
    }

    public function testIdenticalSchema()
    {
        $fromSchema = new Schema();
        $fromSchema->createTable('tl_foobar')->addColumn('foo', 'string');

        $toSchema = new Schema();
        $toSchema->createTable('tl_foobar')->addColumn('foo', 'string');

        $installer = $this->createInstaller($fromSchema, $toSchema);
        $commands = $installer->getCommands();

        $this->assertEmpty($commands);
    }

    public function testAlterTableDropColumn()
    {
        $fromSchema = new Schema();
        $fromSchema->createTable('tl_foobar')->addColumn('foo', 'string');
        $fromSchema->getTable('tl_foobar')->addColumn('bar', 'string');

        $toSchema = new Schema();
        $toSchema->createTable('tl_foobar')->addColumn('foo', 'string');

        $installer = $this->createInstaller($fromSchema, $toSchema);
        $commands = $installer->getCommands();

        $this->assertArrayHasKey('ALTER_DROP', $commands);
        $this->assertEquals('ALTER TABLE tl_foobar DROP bar', reset($commands['ALTER_DROP']));
    }

    public function testAlterTableAddColumn()
    {
        $fromSchema = new Schema();
        $fromSchema->createTable('tl_foobar')->addColumn('foo', 'string');

        $toSchema = new Schema();
        $toSchema->createTable('tl_foobar')->addColumn('foo', 'string');
        $toSchema->getTable('tl_foobar')->addColumn('bar', 'string');

        $installer = $this->createInstaller($fromSchema, $toSchema);
        $commands = $installer->getCommands();

        $this->assertArrayHasKey('ALTER_ADD', $commands);

        $commands = array_values($commands['ALTER_ADD']);

        $this->assertEquals('ALTER TABLE tl_foobar ADD bar VARCHAR(255) NOT NULL', $commands[0]);
    }

    public function testAlterTableWithDecimal()
    {
        $fromSchema = new Schema();
        $fromSchema->createTable('tl_foobar');

        $toSchema = new Schema();
        $toSchema->createTable('tl_foobar')->addColumn('foo', 'decimal', ['precision' => 9, 'scale' => 2]);

        $installer = $this->createInstaller($fromSchema, $toSchema);
        $commands = $installer->getCommands();

        $this->assertArrayHasKey('ALTER_ADD', $commands);

        $commands = array_values($commands['ALTER_ADD']);

        $this->assertEquals('ALTER TABLE tl_foobar ADD foo NUMERIC(9,2) NOT NULL', $commands[0]);
    }

    public function testAlterTableWithCommaDefaultValue()
    {
        $fromSchema = new Schema();
        $fromSchema->createTable('tl_foobar');

        $toSchema = new Schema();
        $toSchema->createTable('tl_foobar')->addColumn('foo', 'string', ['default' => ',']);

        $installer = $this->createInstaller($fromSchema, $toSchema);
        $commands = $installer->getCommands();

        $this->assertArrayHasKey('ALTER_ADD', $commands);

        $commands = array_values($commands['ALTER_ADD']);

        $this->assertEquals("ALTER TABLE tl_foobar ADD foo VARCHAR(255) DEFAULT ',' NOT NULL", $commands[0]);
    }

    public function testAlterTableAddMixedColumns()
    {
        $fromSchema = new Schema();
        $fromSchema->createTable('tl_foobar');

        $toSchema = new Schema();
        $toSchema->createTable('tl_foobar')->addColumn('foo1', 'string');
        $toSchema->getTable('tl_foobar')->addColumn('foo2', 'integer');
        $toSchema->getTable('tl_foobar')->addColumn('foo3', 'decimal', ['precision' => 9, 'scale' => 2]);
        $toSchema->getTable('tl_foobar')->addColumn('foo4', 'string', ['default' => ',']);

        $installer = $this->createInstaller($fromSchema, $toSchema);
        $commands = $installer->getCommands();

        $this->assertArrayHasKey('ALTER_ADD', $commands);

        $commands = array_values($commands['ALTER_ADD']);

        $this->assertCount(4, $commands);
        $this->assertContains('ALTER TABLE tl_foobar ADD foo1 VARCHAR(255) NOT NULL', $commands);
        $this->assertContains('ALTER TABLE tl_foobar ADD foo2 INT NOT NULL', $commands);
        $this->assertContains('ALTER TABLE tl_foobar ADD foo3 NUMERIC(9,2) NOT NULL', $commands);
        $this->assertContains("ALTER TABLE tl_foobar ADD foo4 VARCHAR(255) DEFAULT ',' NOT NULL", $commands);
    }

    private function createInstaller(Schema $fromSchema = null, Schema $toSchema = null)
    {
        $schemaManager = $this->getMockBuilder(AbstractSchemaManager::class)->disableOriginalConstructor()->getMock();
        $connection = $this->getMockBuilder(Connection::class)->disableOriginalConstructor()->getMock();
        $dcaSchema = $this->getMockBuilder(DcaSchemaProvider::class)->disableOriginalConstructor()->getMock();

        $schemaManager
            ->expects($this->any())
            ->method('createSchema')
            ->willReturn($fromSchema)
        ;

        $connection
            ->expects($this->any())
            ->method('getSchemaManager')
            ->willReturn($schemaManager)
        ;

        $connection
            ->expects($this->any())
            ->method('getDatabasePlatform')
            ->willReturn(new MySqlPlatform())
        ;

        $dcaSchema
            ->expects($this->any())
            ->method('createSchema')
            ->willReturn($toSchema)
        ;

        return new Installer($connection, $dcaSchema);
    }
}
