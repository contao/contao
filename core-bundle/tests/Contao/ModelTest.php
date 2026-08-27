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
use Contao\CoreBundle\Tests\Fixtures\Enum\IntBackedEnum;
use Contao\CoreBundle\Tests\Fixtures\Enum\StringBackedEnum;
use Contao\CoreBundle\Tests\TestCase;
use Contao\Model;
use Contao\System;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\AbstractSchemaManager;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use PHPUnit\Framework\Attributes\DataProvider;

class ModelTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $schemaProvider = $this->createStub(SchemaProvider::class);
        $schemaProvider
            ->method('createSchema')
            ->willReturn($this->createSchema(false))
        ;

        $schemaManager = $this->createStub(AbstractSchemaManager::class);
        $schemaManager
            ->method('introspectSchema')
            ->willReturn($this->createSchema(true))
        ;

        $connection = $this->createStub(Connection::class);
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

    public function testGetColumnInfosFromSchema(): void
    {
        $this->assertSame(
            [
                'tl_Foo' => [
                    'string_not_null' => [Types::STRING, 'string', true],
                    'string_null' => [Types::STRING, '1'],
                    'int_not_null' => [Types::INTEGER, 0, true],
                    'int_null' => [Types::INTEGER],
                    'smallint_not_null' => [Types::SMALLINT, 1, true],
                    'smallint_null' => [Types::SMALLINT],
                    'bigint_not_null' => [Types::BIGINT, '9223372036854775808', true],
                    'bigint_null' => [Types::BIGINT],
                    'float_not_null' => [Types::FLOAT, 0.0, true],
                    'float_null' => [Types::FLOAT],
                    'bool_not_null' => [Types::BOOLEAN, false, true],
                    'bool_null' => [Types::BOOLEAN, true],
                    'floatNotNullCamelCase' => [Types::FLOAT, 1.23, true],
                    'dca_only' => [Types::INTEGER],
                ],
            ],
            Model::getColumnInfosFromDca(),
        );
    }

    public function testGetColumnInfosFromDatabase(): void
    {
        $this->assertSame(
            [
                'tl_Foo' => [
                    'string_not_null' => [Types::STRING, 'string', true],
                    'string_null' => [Types::STRING, '1'],
                    'int_not_null' => [Types::INTEGER, 0, true],
                    'int_null' => [Types::INTEGER],
                    'smallint_not_null' => [Types::SMALLINT, 1, true],
                    'smallint_null' => [Types::SMALLINT],
                    'bigint_not_null' => [Types::BIGINT, '9223372036854775808', true],
                    'bigint_null' => [Types::BIGINT],
                    'float_not_null' => [Types::FLOAT, 0.0, true],
                    'float_null' => [Types::FLOAT],
                    'bool_not_null' => [Types::BOOLEAN, false, true],
                    'bool_null' => [Types::BOOLEAN, true],
                    'floatNotNullCamelCase' => [Types::FLOAT, 1.23, true],
                    'database_only' => [Types::INTEGER],
                ],
            ],
            Model::getColumnInfosFromDatabase(),
        );
    }

    #[DataProvider('getDatabaseValues')]
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

    #[DataProvider('getDefaultValues')]
    public function testDefaultValues(string $key, mixed $expected): void
    {
        $fooModel = new class() extends Model {
            protected static $strTable = 'tl_Foo';

            public function __construct()
            {
            }
        };

        $this->assertSame($expected, $fooModel->$key);
    }

    public static function getDefaultValues(): iterable
    {
        yield ['string_not_null', 'string'];

        yield ['string_null', '1'];

        yield ['int_not_null', 0];

        yield ['int_null', null];

        yield ['smallint_not_null', 1];

        yield ['smallint_null', null];

        yield ['bigint_not_null', '9223372036854775808'];

        yield ['bigint_null', null];

        yield ['float_not_null', 0.0];

        yield ['float_null', null];

        yield ['bool_not_null', false];

        yield ['bool_null', true];

        yield ['floatNotNullCamelCase', 1.23];
    }

    #[DataProvider('getDatabaseValues')]
    public function testMagicSetterTypes(string $key, mixed $value, mixed $expected): void
    {
        $fooModel = new class() extends Model {
            protected static $strTable = 'tl_Foo';

            public function __construct()
            {
            }
        };

        $fooModel->$key = $value;

        $this->assertSame($expected, $fooModel->$key);

        if (\is_int($expected)) {
            $this->expectException(\InvalidArgumentException::class);
            $this->expectExceptionMessageMatches('/Setting ".*::\$.*" to type string failed, expected type for (integer|smallint|bigint) column\./');

            $fooModel->$key = 'not_an_integer';
        }
    }

    public static function getDatabaseValues(): iterable
    {
        yield ['string_not_null', 'string', 'string'];

        yield ['string_not_null', null, ''];

        yield ['string_null', 'string', 'string'];

        yield ['string_null', 123, '123'];

        yield ['string_null', 12.3, '12.3'];

        yield ['string_null', true, '1'];

        yield ['string_null', false, ''];

        yield ['string_null', null, null];

        yield ['string_null', [], []];

        yield ['string_null', $object = new \stdClass(), $object];

        yield ['int_not_null', '123', 123];

        yield ['int_null', '123', 123];

        yield ['smallint_not_null', '12', 12];

        yield ['smallint_null', '12', 12];

        yield ['bigint_not_null', (string) PHP_INT_MAX, PHP_INT_MAX];

        yield ['bigint_null', (string) PHP_INT_MAX, PHP_INT_MAX];

        yield ['bigint_not_null', '9223372036854775808', '9223372036854775808'];

        yield ['bigint_null', '9223372036854775808', '9223372036854775808'];

        yield ['bigint_not_null', (string) PHP_INT_MIN, PHP_INT_MIN];

        yield ['bigint_null', (string) PHP_INT_MIN, PHP_INT_MIN];

        yield ['bigint_not_null', '-9223372036854775809', '-9223372036854775809'];

        yield ['bigint_null', '-9223372036854775809', '-9223372036854775809'];

        yield ['float_not_null', '12.3', 12.3];

        yield ['float_null', '12.3', 12.3];

        yield ['bool_not_null', '1', true];

        yield ['bool_null', '1', true];

        yield ['string_null', null, null];

        yield ['int_null', null, null];

        yield ['smallint_null', null, null];

        yield ['bigint_null', null, null];

        yield ['float_null', null, null];

        yield ['bool_null', null, null];

        yield ['floatNotNullCamelCase', '12.3', 12.3];
    }

    #[DataProvider('getEnumFieldValues')]
    public function testResolvesEnumFields(string $enum, mixed $value, \BackedEnum|null $expected): void
    {
        $model = new class($enum) extends Model {
            protected static $strTable = 'tl_foo';

            public function __construct(string $enum)
            {
                $this->arrEnums = [
                    'foo' => $enum,
                ];
            }
        };

        $model->foo = $value;

        $this->assertSame($model->getEnum('foo'), $expected);
    }

    public static function getEnumFieldValues(): iterable
    {
        yield [StringBackedEnum::class, StringBackedEnum::optionB->value, StringBackedEnum::optionB];
        yield [IntBackedEnum::class, IntBackedEnum::optionB->value, IntBackedEnum::optionB];
        yield [StringBackedEnum::class, 'foo', null];
        yield [IntBackedEnum::class, 100, null];
    }

    private function createSchema(bool $fromDatabase): Schema
    {
        $schema = new Schema();
        $table = $schema->createTable('tl_Foo');
        $table->addColumn('string_not_null', Types::STRING, ['notnull' => true, 'default' => 'string']);
        $table->addColumn('string_null', Types::STRING, ['notnull' => false, 'default' => 1]);
        $table->addColumn('int_not_null', Types::INTEGER, ['notnull' => true, 'default' => 0]);
        $table->addColumn('int_null', Types::INTEGER, ['notnull' => false]);
        $table->addColumn('smallint_not_null', Types::SMALLINT, ['notnull' => true, 'default' => '1']);
        $table->addColumn('smallint_null', Types::SMALLINT, ['notnull' => false]);
        $table->addColumn('bigint_not_null', Types::BIGINT, ['notnull' => true, 'default' => '9223372036854775808']);
        $table->addColumn('bigint_null', Types::BIGINT, ['notnull' => false]);
        $table->addColumn('float_not_null', Types::FLOAT, ['notnull' => true, 'default' => '0.0']);
        $table->addColumn('float_null', Types::FLOAT, ['notnull' => false]);
        $table->addColumn('bool_not_null', Types::BOOLEAN, ['notnull' => true]);
        $table->addColumn('bool_null', Types::BOOLEAN, ['notnull' => false, 'default' => true]);
        $table->addColumn('floatNotNullCamelCase', Types::FLOAT, ['notnull' => true, 'default' => 1.23]);

        if ($fromDatabase) {
            $table->addColumn('database_only', Types::INTEGER, ['notnull' => false]);
        } else {
            $table->addColumn('dca_only', Types::INTEGER, ['notnull' => false]);
        }

        return $schema;
    }
}
