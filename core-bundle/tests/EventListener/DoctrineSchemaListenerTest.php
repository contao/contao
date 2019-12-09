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
use Doctrine\DBAL\Event\SchemaIndexDefinitionEventArgs;
use Doctrine\DBAL\Platforms\MySqlPlatform;
use Doctrine\DBAL\Platforms\PostgreSqlPlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\Event\GenerateSchemaEventArgs;

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
