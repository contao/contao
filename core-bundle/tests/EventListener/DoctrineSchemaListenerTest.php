<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2017 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Tests\Doctrine\Schema;

use Contao\CoreBundle\Doctrine\Schema\DcaSchemaProvider;
use Contao\CoreBundle\EventListener\DoctrineSchemaListener;
use Contao\CoreBundle\Tests\Doctrine\DoctrineTestCase;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Event\SchemaIndexDefinitionEventArgs;
use Doctrine\DBAL\Platforms\MySqlPlatform;
use Doctrine\DBAL\Platforms\PostgreSqlPlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\Event\GenerateSchemaEventArgs;

class DoctrineSchemaListenerTest extends DoctrineTestCase
{
    public function testCanBeInstantiated(): void
    {
        $listener = new DoctrineSchemaListener($this->createMock(DcaSchemaProvider::class));

        $this->assertInstanceOf('Contao\CoreBundle\EventListener\DoctrineSchemaListener', $listener);
    }

    public function testAppendsToAnExistingSchema(): void
    {
        $framework = $this->mockContaoFrameworkWithInstaller(
            [
                'tl_files' => [
                    'TABLE_FIELDS' => [
                        'path' => "`path` varchar(1022) NOT NULL default ''",
                    ],
                ],
            ]
        );

        $schema = new Schema();
        $event = new GenerateSchemaEventArgs($this->createMock(EntityManagerInterface::class), $schema);

        $this->assertFalse($schema->hasTable('tl_files'));

        $listener = new DoctrineSchemaListener(new DcaSchemaProvider($framework, $this->mockDoctrineRegistry()));
        $listener->postGenerateSchema($event);

        $this->assertTrue($schema->hasTable('tl_files'));
        $this->assertTrue($schema->getTable('tl_files')->hasColumn('path'));
    }

    public function testChangesTheIndexIfThereIsASubpart(): void
    {
        $connection = $this->createMock(Connection::class);

        $connection
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
        $event = $this
            ->getMockBuilder(SchemaIndexDefinitionEventArgs::class)
            ->disableOriginalConstructor()
            ->setMethods(['getConnection', 'getTable', 'getTableIndex', 'preventDefault'])
            ->getMock()
        ;

        $event
            ->method('getConnection')
            ->willReturn($connection)
        ;

        $event
            ->method('getTable')
            ->willReturn('tl_files')
        ;

        $event
            ->method('getTableIndex')
            ->willReturn($this->getIndexEventArg('path'))
        ;

        $event
            ->expects($this->once())
            ->method('preventDefault')
        ;

        $listener = new DoctrineSchemaListener($this->createMock(DcaSchemaProvider::class));
        $listener->onSchemaIndexDefinition($event);

        $index = $event->getIndex();

        $this->assertInstanceOf('Doctrine\DBAL\Schema\Index', $index);
        $this->assertSame('path', $index->getName());
        $this->assertSame(['path(333)'], $index->getColumns());
    }

    public function testDoesNotChangeTheIndexIfThereIsNoSubpart(): void
    {
        $connection = $this->createMock(Connection::class);

        $connection
            ->method('getDatabasePlatform')
            ->willReturn(new MySqlPlatform())
        ;

        $connection
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

        $event = $this->createMock(SchemaIndexDefinitionEventArgs::class);

        $event
            ->method('getConnection')
            ->willReturn($connection)
        ;

        $event
            ->method('getTable')
            ->willReturn('tl_member')
        ;

        $event
            ->method('getTableIndex')
            ->willReturn($this->getIndexEventArg('username'))
        ;

        $event
            ->expects($this->never())
            ->method('setIndex')
        ;

        $listener = new DoctrineSchemaListener($this->createMock(DcaSchemaProvider::class));
        $listener->onSchemaIndexDefinition($event);
    }

    public function testDoesNotChangeTheIndexOfThePrimaryKeyColumn(): void
    {
        $connection = $this->createMock(Connection::class);

        $connection
            ->method('getDatabasePlatform')
            ->willReturn(new MySqlPlatform())
        ;

        $connection
            ->expects($this->never())
            ->method('fetchAssoc')
        ;

        $event = $this->createMock(SchemaIndexDefinitionEventArgs::class);

        $event
            ->method('getConnection')
            ->willReturn($connection)
        ;

        $event
            ->method('getTableIndex')
            ->willReturn($this->getIndexEventArg('PRIMARY'))
        ;

        $event
            ->expects($this->never())
            ->method('setIndex')
        ;

        $listener = new DoctrineSchemaListener($this->createMock(DcaSchemaProvider::class));
        $listener->onSchemaIndexDefinition($event);
    }

    public function testDoesNotChangeTheIndexOnDatabasePlatformsOtherThanMysql(): void
    {
        $connection = $this->createMock(Connection::class);

        $connection
            ->method('getDatabasePlatform')
            ->willReturn(new PostgreSqlPlatform())
        ;

        $connection
            ->expects($this->never())
            ->method('fetchAssoc')
        ;

        $event = $this->createMock(SchemaIndexDefinitionEventArgs::class);

        $event
            ->method('getConnection')
            ->willReturn($connection)
        ;

        $event
            ->method('getTableIndex')
            ->willReturn($this->getIndexEventArg('pid'))
        ;

        $event
            ->expects($this->never())
            ->method('setIndex')
        ;

        $listener = new DoctrineSchemaListener($this->createMock(DcaSchemaProvider::class));
        $listener->onSchemaIndexDefinition($event);
    }

    /**
     * Returns the index event argument.
     *
     * @param $name
     *
     * @return array
     */
    private function getIndexEventArg($name): array
    {
        return [
            'name' => $name,
            'columns' => ['PRIMARY' === $name ? 'id' : $name],
            'unique' => false,
            'primary' => 'PRIMARY' === $name,
            'flags' => [],
            'options' => [],
        ];
    }
}
