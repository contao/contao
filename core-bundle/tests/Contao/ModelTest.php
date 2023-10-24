<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Contao;

use Contao\CoreBundle\Doctrine\Schema\SchemaProvider;
use Contao\CoreBundle\Tests\TestCase;
use Contao\Model;
use Contao\System;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\AbstractSchemaManager;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;

class ModelTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $schemaProvider = $this->createMock(SchemaProvider::class);
        $schemaProvider
            ->method('createSchema')
            ->willReturn($this->createSchema(false))
        ;

        $schemaManager = $this->createMock(AbstractSchemaManager::class);
        $schemaManager
            ->method('introspectSchema')
            ->willReturn($this->createSchema(true))
        ;

        $connection = $this->createMock(Connection::class);
        $connection
            ->method('createSchemaManager')
            ->willReturn($schemaManager)
        ;

        $container = $this->getContainerWithContaoConfiguration();
        $container->set('database_connection', $connection);
        $container->set('contao.doctrine.schema_provider', $schemaProvider);
        System::setContainer($container);
    }

    protected function tearDown(): void
    {
        $this->resetStaticProperties([Model::class, System::class]);

        parent::tearDown();
    }

    public function testGetColumnCastTypesFromSchema(): void
    {
        $this->assertSame(
            [
                'tl_Foo' => [
                    'int_not_null' => Types::INTEGER,
                    'int_null' => Types::INTEGER,
                    'smallint_not_null' => Types::SMALLINT,
                    'smallint_null' => Types::SMALLINT,
                    'float_not_null' => Types::FLOAT,
                    'float_null' => Types::FLOAT,
                    'bool_not_null' => Types::BOOLEAN,
                    'bool_null' => Types::BOOLEAN,
                    'floatNotNullCamelCase' => Types::FLOAT,
                    'dca_only' => Types::INTEGER,
                ],
            ],
            Model::getColumnCastTypesFromDca(),
        );
    }

    public function testGetColumnCastTypesFromDatabase(): void
    {
        $this->assertSame(
            [
                'tl_Foo' => [
                    'int_not_null' => Types::INTEGER,
                    'int_null' => Types::INTEGER,
                    'smallint_not_null' => Types::SMALLINT,
                    'smallint_null' => Types::SMALLINT,
                    'float_not_null' => Types::FLOAT,
                    'float_null' => Types::FLOAT,
                    'bool_not_null' => Types::BOOLEAN,
                    'bool_null' => Types::BOOLEAN,
                    'floatNotNullCamelCase' => Types::FLOAT,
                    'database_only' => Types::INTEGER,
                ],
            ],
            Model::getColumnCastTypesFromDatabase(),
        );
    }

    /**
     * @dataProvider getDatabaseValues
     */
    public function testConvertToPhpValue(string $key, mixed $value, mixed $expected): void
    {
        $fooModel = new class() extends Model {
            protected static $strTable = 'tl_Foo';

            public function __construct()
            {
            }
        };

        $this->assertSame($expected, $fooModel::convertToPhpValue($key, $value));
    }

    public function getDatabaseValues(): \Generator
    {
        yield ['string_not_null', 'string', 'string'];

        yield ['string_null', 'string', 'string'];

        yield ['int_not_null', '123', 123];

        yield ['int_null', '123', 123];

        yield ['smallint_not_null', '12', 12];

        yield ['smallint_null', '12', 12];

        yield ['float_not_null', '12.3', 12.3];

        yield ['float_null', '12.3', 12.3];

        yield ['bool_not_null', '1', true];

        yield ['bool_null', '1', true];

        yield ['string_null', null, null];

        yield ['int_null', null, null];

        yield ['smallint_null', null, null];

        yield ['float_null', null, null];

        yield ['bool_null', null, null];

        yield ['floatNotNullCamelCase', '12.3', 12.3];
    }

    private function createSchema(bool $fromDatabase): Schema
    {
        $schema = new Schema();
        $table = $schema->createTable('tl_Foo');
        $table->addColumn('string_not_null', Types::STRING, ['notnull' => true]);
        $table->addColumn('string_null', Types::STRING, ['notnull' => false]);
        $table->addColumn('int_not_null', Types::INTEGER, ['notnull' => true]);
        $table->addColumn('int_null', Types::INTEGER, ['notnull' => false]);
        $table->addColumn('smallint_not_null', Types::SMALLINT, ['notnull' => true]);
        $table->addColumn('smallint_null', Types::SMALLINT, ['notnull' => false]);
        $table->addColumn('float_not_null', Types::FLOAT, ['notnull' => true]);
        $table->addColumn('float_null', Types::FLOAT, ['notnull' => false]);
        $table->addColumn('bool_not_null', Types::BOOLEAN, ['notnull' => true]);
        $table->addColumn('bool_null', Types::BOOLEAN, ['notnull' => false]);
        $table->addColumn('floatNotNullCamelCase', Types::FLOAT, ['notnull' => true]);

        if ($fromDatabase) {
            $table->addColumn('database_only', Types::INTEGER, ['notnull' => false]);
        } else {
            $table->addColumn('dca_only', Types::INTEGER, ['notnull' => false]);
        }

        return $schema;
    }
}
