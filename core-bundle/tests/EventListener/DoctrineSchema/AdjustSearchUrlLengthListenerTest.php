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
    /**
     * @group legacy
     *
     * @expectedDeprecation Since contao/core-bundle 4.9: The tl_search.url field length has been automatically reduced.%s
     */
    public function testAdjustsLengthIfRowFormatIsEngineIsNotInnoDB(): void
    {
        $schema = $this->getSchema([], ['engine' => 'MyISAM']);
        $event = new GenerateSchemaEventArgs($this->createMock(EntityManagerInterface::class), $schema);

        (new AdjustSearchUrlLengthListener($this->getConnection()))($event);

        $this->assertSame(1000, $schema->getTable('tl_search')->getColumn('url')->getLength());
    }

    /**
     * @group legacy
     *
     * @expectedDeprecation Since contao/core-bundle 4.9: The tl_search.url field length has been automatically reduced.%s
     */
    public function testAdjustsLengthIfRowFormatIsNotDynamicOrCompressed(): void
    {
        $schema = $this->getSchema([], ['row_format' => 'COMPACT']);
        $event = new GenerateSchemaEventArgs($this->createMock(EntityManagerInterface::class), $schema);

        (new AdjustSearchUrlLengthListener($this->getConnection()))($event);

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

    /**
     * @group legacy
     *
     * @expectedDeprecation Since contao/core-bundle 4.9: The tl_search.url field length has been automatically reduced.%s
     */
    public function testAdjustsLengthIfLargePrefixIsDisabled(): void
    {
        $connection = $this->getConnection([
            "SHOW VARIABLES LIKE 'innodb_large_prefix'" => ['Value' => 'off'],
            'SELECT @@version as Value' => ['Value' => '5.1'],
        ]);

        $schema = $this->getSchema();
        $event = new GenerateSchemaEventArgs($this->createMock(EntityManagerInterface::class), $schema);

        (new AdjustSearchUrlLengthListener($connection))($event);

        $this->assertSame(767, $schema->getTable('tl_search')->getColumn('url')->getLength());
    }

    /**
     * @group legacy
     *
     * @expectedDeprecation Since contao/core-bundle 4.9: The tl_search.url field length has been automatically reduced.%s
     */
    public function testAdjustsLengthIfCollationIsNotAsciiBin(): void
    {
        $connection = $this->getConnection([
            "SHOW VARIABLES LIKE 'innodb_large_prefix'" => ['Value' => 'on'],
            'SELECT @@version as Value' => ['Value' => '5.1'],
        ]);

        $schema = $this->getSchema(['platformOptions' => ['collation' => 'utf8mb4_unicode_ci']]);
        $event = new GenerateSchemaEventArgs($this->createMock(EntityManagerInterface::class), $schema);

        (new AdjustSearchUrlLengthListener($connection))($event);

        $this->assertSame(768, $schema->getTable('tl_search')->getColumn('url')->getLength());
    }

    /**
     * @group legacy
     *
     * @expectedDeprecation Since contao/core-bundle 4.9: The tl_search.url field length has been automatically reduced.%s
     */
    public function testAdjustsLengthIfCollationIsNotAsciiBinAndLargePrefixIsNotEnabled(): void
    {
        $connection = $this->getConnection([
            "SHOW VARIABLES LIKE 'innodb_large_prefix'" => ['Value' => 'off'],
            'SELECT @@version as Value' => ['Value' => '5.1'],
        ]);

        $schema = $this->getSchema(['platformOptions' => ['collation' => 'utf8mb4_unicode_ci']]);
        $event = new GenerateSchemaEventArgs($this->createMock(EntityManagerInterface::class), $schema);

        (new AdjustSearchUrlLengthListener($connection))($event);

        $this->assertSame(191, $schema->getTable('tl_search')->getColumn('url')->getLength());
    }

    private function getConnection(array $returnMap = []): Connection
    {
        $connection = $this->createMock(Connection::class);
        $connection
            ->expects($this->exactly(\count($returnMap)))
            ->method('fetchAssociative')
            ->willReturnCallback(static fn (string $query) => $returnMap[$query] ?? null)
        ;

        return $connection;
    }

    private function getSchema(array $fieldOptions = [], array $tableOptions = []): Schema
    {
        $fieldOptions = array_merge(
            [
                'length' => 2048,
                'platformOptions' => ['collation' => 'ascii_bin'],
            ],
            $fieldOptions
        );

        $tableOptions = array_merge(
            [
                'charset' => 'utf8mb4',
                'engine' => 'InnoDB',
                'row_format' => 'DYNAMIC',
            ],
            $tableOptions
        );

        $column = new Column('url', new StringType(), $fieldOptions);
        $index = new Index('url', ['url'], true);
        $table = new Table('tl_search', [$column], [$index], [], [], $tableOptions);

        return new Schema([$table]);
    }
}
