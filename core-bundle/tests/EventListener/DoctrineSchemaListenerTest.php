<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2016 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Test\Doctrine\Schema;

use Contao\CoreBundle\Doctrine\Schema\DcaSchemaProvider;
use Contao\CoreBundle\EventListener\DoctrineSchemaListener;
use Contao\CoreBundle\Test\TestCase;
use Contao\Database\Installer;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Event\SchemaIndexDefinitionEventArgs;
use Doctrine\DBAL\Platforms\MySqlPlatform;
use Doctrine\DBAL\Platforms\PostgreSqlPlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\ORM\Tools\Event\GenerateSchemaEventArgs;

/**
 * Tests the DoctrineSchemaListener class.
 *
 * @author Andreas Schempp <https://github.com/aschempp>
 */
class DoctrineSchemaListenerTest extends TestCase
{
    /**
     * Tests the object instantiation.
     */
    public function testInstantiation()
    {
        $provider = new DcaSchemaProvider($this->mockContainerWithContaoScopes());
        $listener = new DoctrineSchemaListener($provider);

        $this->assertInstanceOf('Contao\CoreBundle\EventListener\DoctrineSchemaListener', $listener);
    }

    /**
     * Tests the postGenerateSchema() method.
     */
    public function testPostGenerateSchema()
    {
        $provider = $this->getProvider(
            [
                'tl_files' => [
                    'TABLE_FIELDS' => [
                        'path' => "`path` varchar(1022) NOT NULL default ''",
                    ],
                ],
            ]
        );

        $schema = new Schema();
        $event = new GenerateSchemaEventArgs($this->getMock('Doctrine\ORM\EntityManagerInterface'), $schema);

        $this->assertFalse($schema->hasTable('tl_files'));

        $listener = new DoctrineSchemaListener($provider);
        $listener->postGenerateSchema($event);

        $this->assertTrue($schema->hasTable('tl_files'));
        $this->assertTrue($schema->getTable('tl_files')->hasColumn('path'));
    }

    /**
     * Tests the onSchemaIndexDefinition() method with subpart.
     */
    public function testOnSchemaIndexDefinitionWithSubpart()
    {
        /** @var Connection|\PHPUnit_Framework_MockObject_MockObject $event */
        $connection = $this->getMock('Doctrine\DBAL\Connection', ['getDatabasePlatform', 'fetchAssoc'], [], '', false);

        $connection
            ->expects($this->any())
            ->method('getDatabasePlatform')
            ->willReturn(new MySqlPlatform())
        ;

        $connection
            ->expects($this->once())
            ->method('fetchAssoc')
            ->with("SHOW INDEX FROM tl_files WHERE Key_name='path'")
            ->willReturn(
                [
                    'Table' => 'tl_files',
                    'Non_unique' => '1',
                    'Key_name' => 'path',
                    'Seq_in_index' => '1',
                    'Column_name' => 'path',
                    'Collation' => 'A',
                    'Cardinality' => '903',
                    'Sub_part' => '333',
                    'Packed' => null,
                    'Null' => null,
                    'Index_type' => 'BTREE',
                    'Comment' => '',
                    'Index_comment' => '',
                ]
            )
        ;

        /** @var SchemaIndexDefinitionEventArgs|\PHPUnit_Framework_MockObject_MockObject $event */
        $event = $this->getMock(
            'Doctrine\DBAL\Event\SchemaIndexDefinitionEventArgs',
            ['getConnection', 'getTable', 'getTableIndex', 'preventDefault'],
            [],
            '',
            false
        );

        $event
            ->expects($this->any())
            ->method('getConnection')
            ->willReturn($connection)
        ;

        $event
            ->expects($this->any())
            ->method('getTable')
            ->willReturn('tl_files')
        ;

        $event
            ->expects($this->any())
            ->method('getTableIndex')
            ->willReturn($this->getIndexEventArg('path'))
        ;

        $event
            ->expects($this->once())
            ->method('preventDefault')
        ;

        $listener = new DoctrineSchemaListener(
            $this->getMock('Contao\CoreBundle\Doctrine\Schema\DcaSchemaProvider', [], [], '', false)
        );

        $listener->onSchemaIndexDefinition($event);

        $index = $event->getIndex();

        $this->assertInstanceOf('Doctrine\DBAL\Schema\Index', $index);
        $this->assertEquals('path', $index->getName());
        $this->assertEquals(['path(333)'], $index->getColumns());
    }

    /**
     * Tests the onSchemaIndexDefinition() method without subpart.
     */
    public function testOnSchemaIndexDefinitionWithoutSubpart()
    {
        /** @var Connection|\PHPUnit_Framework_MockObject_MockObject $event */
        $connection = $this->getMock('Doctrine\DBAL\Connection', ['getDatabasePlatform', 'fetchAssoc'], [], '', false);

        $connection
            ->expects($this->any())
            ->method('getDatabasePlatform')
            ->willReturn(new MySqlPlatform())
        ;

        $connection
            ->expects($this->any())
            ->method('fetchAssoc')
            ->willReturn(
                [
                    'Table' => 'tl_member',
                    'Non_unique' => '0',
                    'Key_name' => 'username',
                    'Seq_in_index' => '1',
                    'Column_name' => 'username',
                    'Collation' => 'A',
                    'Cardinality' => null,
                    'Sub_part' => null,
                    'Packed' => null,
                    'Null' => 'YES',
                    'Index_type' => 'BTREE',
                    'Comment' => '',
                    'Index_comment' => '',
                ]
            )
        ;

        /** @var SchemaIndexDefinitionEventArgs|\PHPUnit_Framework_MockObject_MockObject $event */
        $event = $this->getMock('Doctrine\DBAL\Event\SchemaIndexDefinitionEventArgs', [], [], '', false);

        $event
            ->expects($this->any())
            ->method('getConnection')
            ->willReturn($connection)
        ;

        $event
            ->expects($this->any())
            ->method('getTable')
            ->willReturn('tl_member')
        ;

        $event
            ->expects($this->any())
            ->method('getTableIndex')
            ->willReturn($this->getIndexEventArg('username'))
        ;

        $event
            ->expects($this->never())
            ->method('setIndex')
        ;

        $listener = new DoctrineSchemaListener(
            $this->getMock('Contao\CoreBundle\Doctrine\Schema\DcaSchemaProvider', [], [], '', false)
        );

        $listener->onSchemaIndexDefinition($event);
    }

    /**
     * Tests that the onSchemaIndexDefinition() method ignores the primary key.
     */
    public function testOnSchemaIndexDefinitionIgnoresPrimaryKey()
    {
        /** @var Connection|\PHPUnit_Framework_MockObject_MockObject $event */
        $connection = $this->getMock('Doctrine\DBAL\Connection', ['getDatabasePlatform', 'fetchAssoc'], [], '', false);

        $connection
            ->expects($this->any())
            ->method('getDatabasePlatform')
            ->willReturn(new MySqlPlatform())
        ;

        $connection
            ->expects($this->never())
            ->method('fetchAssoc')
        ;

        /** @var SchemaIndexDefinitionEventArgs|\PHPUnit_Framework_MockObject_MockObject $event */
        $event = $this->getMock('Doctrine\DBAL\Event\SchemaIndexDefinitionEventArgs', [], [], '', false);

        $event
            ->expects($this->any())
            ->method('getConnection')
            ->willReturn($connection)
        ;

        $event
            ->expects($this->any())
            ->method('getTableIndex')
            ->willReturn($this->getIndexEventArg('PRIMARY'))
        ;

        $event
            ->expects($this->never())
            ->method('setIndex')
        ;

        $listener = new DoctrineSchemaListener(
            $this->getMock('Contao\CoreBundle\Doctrine\Schema\DcaSchemaProvider', [], [], '', false)
        );

        $listener->onSchemaIndexDefinition($event);
    }

    /**
     * Tests that the onSchemaIndexDefinition() method ignores non MySQL platforms.
     */
    public function testOnSchemaIndexDefinitionIgnoresNonMySqlPlatform()
    {
        /** @var Connection|\PHPUnit_Framework_MockObject_MockObject $event */
        $connection = $this->getMock('Doctrine\DBAL\Connection', ['getDatabasePlatform', 'fetchAssoc'], [], '', false);

        $connection
            ->expects($this->any())
            ->method('getDatabasePlatform')
            ->willReturn(new PostgreSqlPlatform())
        ;

        $connection
            ->expects($this->never())
            ->method('fetchAssoc')
        ;

        /** @var SchemaIndexDefinitionEventArgs|\PHPUnit_Framework_MockObject_MockObject $event */
        $event = $this->getMock('Doctrine\DBAL\Event\SchemaIndexDefinitionEventArgs', [], [], '', false);

        $event
            ->expects($this->any())
            ->method('getConnection')
            ->willReturn($connection)
        ;

        $event
            ->expects($this->any())
            ->method('getTableIndex')
            ->willReturn($this->getIndexEventArg('pid'))
        ;

        $event
            ->expects($this->never())
            ->method('setIndex')
        ;

        $listener = new DoctrineSchemaListener(
            $this->getMock('Contao\CoreBundle\Doctrine\Schema\DcaSchemaProvider', [], [], '', false)
        );

        $listener->onSchemaIndexDefinition($event);
    }

    /**
     * Returns a DCA schema provider.
     *
     * @param array $dca
     * @param array $file
     *
     * @return DcaSchemaProvider
     */
    protected function getProvider(array $dca = [], array $file = [])
    {
        /** @var Connection|\PHPUnit_Framework_MockObject_MockObject $event */
        $connection = $this->getMock('Doctrine\DBAL\Connection', ['getDatabasePlatform'], [], '', false);

        $connection
            ->expects($this->any())
            ->method('getDatabasePlatform')
            ->willReturn(new MySqlPlatform())
        ;

        /** @var Installer|\PHPUnit_Framework_MockObject_MockObject $event */
        $installer = $this->getMock('Contao\Database\Installer', ['getFromDca', 'getFromFile']);

        $installer
            ->expects($this->any())
            ->method('getFromDca')
            ->willReturn($dca)
        ;

        $installer
            ->expects($this->any())
            ->method('getFromFile')
            ->willReturn($file)
        ;

        $container = $this->mockContainerWithContaoScopes();

        $container->set(
            'contao.framework',
            $this->mockContaoFramework(
                null,
                null,
                [],
                ['Contao\Database\Installer' => $installer]
            )
        );

        $container->set('database_connection', $connection);

        return new DcaSchemaProvider($container);
    }

    /**
     * Returns the index event argument.
     *
     * @param $name
     *
     * @return array
     */
    private function getIndexEventArg($name)
    {
        return [
            'name' => $name,
            'columns' => [($name === 'PRIMARY' ? 'id' : $name)],
            'unique' => false,
            'primary' => ($name === 'PRIMARY'),
            'flags' => [],
            'options' => [],
        ];
    }
}
