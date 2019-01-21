<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\EventListener;

use Contao\CoreBundle\Doctrine\Schema\DcaSchemaProvider;
use Contao\CoreBundle\EventListener\DoctrineSchemaListener;
use Contao\CoreBundle\Tests\Doctrine\DoctrineTestCase;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver\ResultStatement;
use Doctrine\DBAL\Event\SchemaIndexDefinitionEventArgs;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Platforms\MySqlPlatform;
use Doctrine\DBAL\Platforms\PostgreSqlPlatform;
use Doctrine\DBAL\Schema\Index;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\Event\GenerateSchemaEventArgs;
use PHPUnit\Framework\MockObject\MockObject;

class DoctrineSchemaListenerTest extends DoctrineTestCase
{
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
        if (method_exists(AbstractPlatform::class, 'supportsColumnLengthIndexes')) {
            $this->markTestSkipped('This test is only relevant for doctrine/dbal < 2.9');
        }

        $result = $this->createMock(ResultStatement::class);
        $result
            ->method('fetch')
            ->willReturnOnConsecutiveCalls(
                [
                    'Table' => 'tl_recipients',
                    'Non_unique' => '0',
                    'Key_name' => 'pid_email',
                    'Seq_in_index' => '1',
                    'Column_name' => 'pid',
                    'Collation' => 'A',
                    'Cardinality' => '2',
                    'Sub_part' => null,
                    'Packed' => null,
                    'Null' => '',
                    'Index_type' => 'BTREE',
                    'Comment' => '',
                    'Index_comment' => '',
                ],
                [
                    'Table' => 'tl_recipients',
                    'Non_unique' => '0',
                    'Key_name' => 'pid_email',
                    'Seq_in_index' => '2',
                    'Column_name' => 'email',
                    'Collation' => 'A',
                    'Cardinality' => '2',
                    'Sub_part' => '191',
                    'Packed' => null,
                    'Null' => '',
                    'Index_type' => 'BTREE',
                    'Comment' => '',
                    'Index_comment' => '',
                ],
                false
            )
        ;

        $connection = $this->createMock(Connection::class);
        $connection
            ->method('getDatabasePlatform')
            ->willReturn(new MySqlPlatform())
        ;

        $connection
            ->expects($this->once())
            ->method('executeQuery')
            ->with("SHOW INDEX FROM tl_recipients WHERE Key_name='path'")
            ->willReturn($result)
        ;

        /** @var SchemaIndexDefinitionEventArgs|MockObject $event */
        $event = $this
            ->getMockBuilder(SchemaIndexDefinitionEventArgs::class)
            ->disableOriginalConstructor()
            ->setMethods(['getConnection', 'getTable', 'getTableIndex', 'preventDefault', 'setIndex'])
            ->getMock()
        ;

        $event
            ->method('getConnection')
            ->willReturn($connection)
        ;

        $event
            ->method('getTable')
            ->willReturn('tl_recipients')
        ;

        $event
            ->method('getTableIndex')
            ->willReturn($this->getIndexEventArg('path'))
        ;

        $event
            ->expects($this->once())
            ->method('setIndex')
            ->with($this->callback(
                function (Index $index) {
                    $this->assertSame(['pid', 'email(191)'], $index->getColumns());

                    return true;
                }
            ))
        ;

        $event
            ->expects($this->once())
            ->method('preventDefault')
        ;

        $listener = new DoctrineSchemaListener($this->createMock(DcaSchemaProvider::class));
        $listener->onSchemaIndexDefinition($event);
    }

    public function testDoesNotChangeTheIndexIfThereIsNoSubpart(): void
    {
        if (method_exists(AbstractPlatform::class, 'supportsColumnLengthIndexes')) {
            $this->markTestSkipped('This test is only relevant for doctrine/dbal < 2.9');
        }

        $result = $this->createMock(ResultStatement::class);
        $result
            ->method('fetch')
            ->willReturnOnConsecutiveCalls(
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
                ],
                false
            )
        ;

        $connection = $this->createMock(Connection::class);
        $connection
            ->method('getDatabasePlatform')
            ->willReturn(new MySqlPlatform())
        ;

        $connection
            ->method('executeQuery')
            ->willReturn($result)
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
            ->expects($this->once())
            ->method('setIndex')
            ->with($this->callback(
                function (Index $index) {
                    $this->assertSame(['username'], $index->getColumns());

                    return true;
                }
            ))
        ;

        $event
            ->expects($this->once())
            ->method('preventDefault')
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
     * @return array<string,string[]|string|bool>
     */
    private function getIndexEventArg(string $name): array
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
