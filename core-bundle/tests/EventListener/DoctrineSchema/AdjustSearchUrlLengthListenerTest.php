<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\EventListener\DoctrineSchema;

use Contao\CoreBundle\EventListener\DoctrineSchema\AdjustSearchUrlLengthListener;
use Contao\CoreBundle\Tests\TestCase;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Schema\Index;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Types\StringType;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\Event\GenerateSchemaEventArgs;

class AdjustSearchUrlLengthListenerTest extends TestCase
{
    public function testAdjustsLengthIfRowFormatIsEngineIsNotInnoDB(): void
    {
        $connection = $this->getConnection();

        $schema = $this->getSchema([], ['engine' => 'MyISAM']);

        $event = new GenerateSchemaEventArgs($this->createMock(EntityManagerInterface::class), $schema);

        (new AdjustSearchUrlLengthListener($connection))($event);

        $this->assertSame(1000, $schema->getTable('tl_search')->getColumn('url')->getLength());
    }

    public function testAdjustsLengthIfRowFormatIsNotDynamicOrCompressed(): void
    {
        $connection = $this->getConnection();

        $schema = $this->getSchema([], ['row_format' => 'COMPACT']);

        $event = new GenerateSchemaEventArgs($this->createMock(EntityManagerInterface::class), $schema);

        (new AdjustSearchUrlLengthListener($connection))($event);

        $this->assertSame(767, $schema->getTable('tl_search')->getColumn('url')->getLength());
    }

    public function testDoesNotAdjustLengthIfLargePrefixVariableIsNotAvailable(): void
    {
        $connection = $this->getConnection(["SHOW VARIABLES LIKE 'innodb_large_prefix'" => false]);

        $schema = $this->getSchema();

        $event = new GenerateSchemaEventArgs($this->createMock(EntityManagerInterface::class), $schema);

        (new AdjustSearchUrlLengthListener($connection))($event);

        $this->assertSame(2048, $schema->getTable('tl_search')->getColumn('url')->getLength());
    }

    public function testDoesNotAdjustLengthIfServerVersionIsHighEnough(): void
    {
        $connection = $this->getConnection([
            "SHOW VARIABLES LIKE 'innodb_large_prefix'" => ['Value' => 'on'],
            'SELECT @@version as Value' => ['Value' => '5.7.7'],
        ]);

        $schema = $this->getSchema();

        $event = new GenerateSchemaEventArgs($this->createMock(EntityManagerInterface::class), $schema);

        (new AdjustSearchUrlLengthListener($connection))($event);

        $this->assertSame(2048, $schema->getTable('tl_search')->getColumn('url')->getLength());
    }

    public function testAdjustsLengthIfLargePrefixIsDisabled(): void
    {
        $connection = $this->createMock(Connection::class);
        $connection
            ->expects($this->exactly(2))
            ->method('fetchAssociative')
            ->willReturnCallback(
                static function (string $query) {
                    switch ($query) {
                        case "SHOW VARIABLES LIKE 'innodb_large_prefix'": return ['Value' => 'off'];

                        case 'SELECT @@version as Value': return ['Value' => '5.1'];
                    }

                    return null;
                }
            )
        ;

        $schema = $this->getSchema();

        $event = new GenerateSchemaEventArgs($this->createMock(EntityManagerInterface::class), $schema);

        (new AdjustSearchUrlLengthListener($connection))($event);

        $this->assertSame(767, $schema->getTable('tl_search')->getColumn('url')->getLength());
    }

    private function getConnection(array $returnMap = []): Connection
    {
        $connection = $this->createMock(Connection::class);
        $connection
            ->expects($this->exactly(\count($returnMap)))
            ->method('fetchAssociative')
            ->willReturnCallback(
                static function (string $query) use ($returnMap) {
                    return $returnMap[$query] ?? null;
                }
            )
        ;

        return $connection;
    }

    private function getSchema(array $fieldOptions = [], array $tableOptions = [])
    {
        $fieldOptions = array_merge([
            'length' => 2048,
            'platformOptions' => ['collation' => 'ascii_bin'],
        ], $fieldOptions);

        $tableOptions = array_merge([
            'charset' => 'utf8mb4',
            'engine' => 'InnoDB',
            'row_format' => 'DYNAMIC',
        ], $tableOptions);

        $column = new Column('url', new StringType(), $fieldOptions);
        $index = new Index('url', ['url'], true);
        $table = new Table('tl_search', [$column], [$index], [], 0, $tableOptions);

        return new Schema([$table]);
    }
}
