<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Doctrine\Backup;

use Contao\CoreBundle\Doctrine\Backup\Backup;
use Contao\CoreBundle\Doctrine\Backup\BackupManagerException;
use Contao\CoreBundle\Doctrine\Backup\Config\CreateConfig;
use Contao\CoreBundle\Doctrine\Backup\Dumper;
use Contao\TestCase\ContaoTestCase;
use Doctrine\DBAL\Cache\ArrayResult;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Platforms\MySQLPlatform;
use Doctrine\DBAL\Result;
use Doctrine\DBAL\Schema\AbstractSchemaManager;
use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Schema\View;
use Doctrine\DBAL\Types\Type;
use Doctrine\DBAL\Types\Types;
use PHPUnit\Framework\MockObject\MockObject;

class DumperTest extends ContaoTestCase
{
    /**
     * @dataProvider successfulDumpProvider
     */
    public function testSuccessfulDump(array $tables, array $views, array $queries, array $expectedDump): void
    {
        $backup = new Backup('backup__20211101141254.sql');

        $dumper = new Dumper();
        $connection = $this->mockConnection($tables, $views, $queries);
        $config = new CreateConfig($backup);

        $this->assertSame($expectedDump, iterator_to_array($dumper->dump($connection, $config), false));
    }

    public function testUnsuccessfulDump(): void
    {
        $this->expectException(BackupManagerException::class);
        $this->expectExceptionMessage('Error!');

        $backup = new Backup('backup__20211101141254.sql');

        $connection = $this->createMock(Connection::class);
        $connection
            ->expects($this->once())
            ->method('createSchemaManager')
            ->willThrowException(new \Exception('Error!'))
        ;

        $dumper = new Dumper();
        $config = (new CreateConfig($backup))->withGzCompression(false);

        iterator_to_array($dumper->dump($connection, $config), false);
    }

    public function successfulDumpProvider(): \Generator
    {
        yield 'Empty table without data' => [
            [new Table('tl_page', [new Column('foobar', Type::getType(Types::STRING))])],
            [],
            [
                'SELECT `foobar` AS `foobar` FROM `tl_page`' => [],
            ],
            [
                'SET FOREIGN_KEY_CHECKS = 0;',
                '-- BEGIN STRUCTURE tl_page',
                'DROP TABLE IF EXISTS `tl_page`;',
                'CREATE TABLE `tl_page` (`foobar` VARCHAR(255) NOT NULL) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB;',
                '-- BEGIN DATA tl_page',
                'SET FOREIGN_KEY_CHECKS = 1;',
            ],
        ];

        yield 'Table with data' => [
            [
                new Table('tl_page', [
                    new Column('stringCol', Type::getType(Types::STRING)),
                    new Column('integerCol', Type::getType(Types::INTEGER)),
                    new Column('booleanCol', Type::getType(Types::BOOLEAN)),
                ]),
            ],
            [],
            [
                'SELECT `stringCol` AS `stringCol`, `integerCol` AS `integerCol`, `booleanCol` AS `booleanCol` FROM `tl_page`' => [
                    [
                        'stringCol' => 'value1',
                        'integerCol' => '42',
                        'booleanCol' => '1',
                    ],
                    [
                        'stringCol' => '',
                        'integerCol' => null,
                        'booleanCol' => '0',
                    ],
                ],
            ],
            [
                'SET FOREIGN_KEY_CHECKS = 0;',
                '-- BEGIN STRUCTURE tl_page',
                'DROP TABLE IF EXISTS `tl_page`;',
                'CREATE TABLE `tl_page` (`stringCol` VARCHAR(255) NOT NULL, `integerCol` INT NOT NULL, `booleanCol` TINYINT(1) NOT NULL) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB;',
                '-- BEGIN DATA tl_page',
                "INSERT INTO `tl_page` (`stringCol`, `integerCol`, `booleanCol`) VALUES ('value1', 42, 1);",
                "INSERT INTO `tl_page` (`stringCol`, `integerCol`, `booleanCol`) VALUES ('', NULL, 0);",
                'SET FOREIGN_KEY_CHECKS = 1;',
            ],
        ];

        yield 'Table with float and integer data' => [
            [
                new Table('tl_page', [
                    new Column('stringCol', Type::getType(Types::STRING)),
                    new Column('integerCol', Type::getType(Types::INTEGER)),
                    new Column('floatCol', Type::getType(Types::FLOAT)),
                    new Column('bigintCol', Type::getType(Types::BIGINT)),
                    new Column('decimalCol', Type::getType(Types::DECIMAL)),
                    new Column('booleanCol', Type::getType(Types::BOOLEAN)),
                ]),
            ],
            [],
            [
                'SELECT `stringCol` AS `stringCol`, `integerCol` AS `integerCol`, `floatCol` AS `floatCol`, `bigintCol` AS `bigintCol`, `decimalCol` AS `decimalCol`, `booleanCol` AS `booleanCol` FROM `tl_page`' => [
                    [
                        'stringCol' => 'value1',
                        'integerCol' => '42',
                        'floatCol' => '4.2',
                        'bigintCol' => '92233720368547758079223372036854775807',
                        'decimalCol' => '4.2',
                        'booleanCol' => '1',
                    ],
                    [
                        'stringCol' => 'value1',
                        'integerCol' => 42,
                        'floatCol' => 4.2,
                        'bigintCol' => '92233720368547758079223372036854775807',
                        'decimalCol' => 4.2,
                        'booleanCol' => 1,
                    ],
                ],
            ],
            [
                'SET FOREIGN_KEY_CHECKS = 0;',
                '-- BEGIN STRUCTURE tl_page',
                'DROP TABLE IF EXISTS `tl_page`;',
                'CREATE TABLE `tl_page` (`stringCol` VARCHAR(255) NOT NULL, `integerCol` INT NOT NULL, `floatCol` DOUBLE PRECISION NOT NULL, `bigintCol` BIGINT NOT NULL, `decimalCol` NUMERIC(10, 0) NOT NULL, `booleanCol` TINYINT(1) NOT NULL) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB;',
                '-- BEGIN DATA tl_page',
                "INSERT INTO `tl_page` (`stringCol`, `integerCol`, `floatCol`, `bigintCol`, `decimalCol`, `booleanCol`) VALUES ('value1', 42, '4.2', '92233720368547758079223372036854775807', '4.2', 1);",
                "INSERT INTO `tl_page` (`stringCol`, `integerCol`, `floatCol`, `bigintCol`, `decimalCol`, `booleanCol`) VALUES ('value1', 42, '4.2', '92233720368547758079223372036854775807', '4.2', 1);",
                'SET FOREIGN_KEY_CHECKS = 1;',
            ],
        ];

        yield 'Multiple tables with data' => [
            [
                new Table('tl_page', [new Column('foobar', Type::getType(Types::STRING))]),
                new Table('tl_news', [new Column('foobar', Type::getType(Types::STRING))]),
            ],
            [],
            [
                'SELECT `foobar` AS `foobar` FROM `tl_page`' => [
                    [
                        'foobar' => 'value1',
                    ],
                    [
                        'foobar' => null,
                    ],
                ],
                'SELECT `foobar` AS `foobar` FROM `tl_news`' => [
                    [
                        'foobar' => 'value1',
                    ],
                ],
            ],
            [
                'SET FOREIGN_KEY_CHECKS = 0;',
                '-- BEGIN STRUCTURE tl_page',
                'DROP TABLE IF EXISTS `tl_page`;',
                'CREATE TABLE `tl_page` (`foobar` VARCHAR(255) NOT NULL) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB;',
                '-- BEGIN DATA tl_page',
                "INSERT INTO `tl_page` (`foobar`) VALUES ('value1');",
                'INSERT INTO `tl_page` (`foobar`) VALUES (NULL);',
                '-- BEGIN STRUCTURE tl_news',
                'DROP TABLE IF EXISTS `tl_news`;',
                'CREATE TABLE `tl_news` (`foobar` VARCHAR(255) NOT NULL) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB;',
                '-- BEGIN DATA tl_news',
                "INSERT INTO `tl_news` (`foobar`) VALUES ('value1');",
                'SET FOREIGN_KEY_CHECKS = 1;',
            ],
        ];

        yield 'Table structure and views' => [
            [new Table('tl_page', [new Column('foobar', Type::getType(Types::STRING))])],
            [new View('view_name', 'SELECT `tl_page`.`id` AS `id` FROM `tl_page`')],
            [
                'SELECT `foobar` AS `foobar` FROM `tl_page`' => [],
            ],
            [
                'SET FOREIGN_KEY_CHECKS = 0;',
                '-- BEGIN STRUCTURE tl_page',
                'DROP TABLE IF EXISTS `tl_page`;',
                'CREATE TABLE `tl_page` (`foobar` VARCHAR(255) NOT NULL) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB;',
                '-- BEGIN DATA tl_page',
                '-- BEGIN VIEW view_name',
                'CREATE OR REPLACE VIEW `view_name` AS SELECT `tl_page`.`id` AS `id` FROM `tl_page`;',
                'SET FOREIGN_KEY_CHECKS = 1;',
            ],
        ];

        yield 'Table with binary data' => [
            [new Table('tl_page', [
                new Column('x_string', Type::getType(Types::STRING), ['platformOptions' => ['charset' => 'utf8mb4']]),
                new Column('x_array', Type::getType(Types::ARRAY)),
                new Column('x_ascii', Type::getType(Types::ASCII_STRING)),
                new Column('x_binary', Type::getType(Types::BINARY)),
                new Column('x_blob', Type::getType(Types::BLOB)),
                new Column('x_object', Type::getType(Types::OBJECT)),
                new Column('x_simple_array', Type::getType(Types::SIMPLE_ARRAY)),
            ])],
            [],
            [
                'SELECT `x_string` AS `x_string`, `x_array` AS `x_array`, `x_ascii` AS `x_ascii`, `x_binary` AS `x_binary`, `x_blob` AS `x_blob`, `x_object` AS `x_object`, `x_simple_array` AS `x_simple_array` FROM `tl_page`' => [
                    [
                        'x_string' => 'ascii',
                        'x_array' => serialize(['ascii']),
                        'x_ascii' => 'ascii',
                        'x_binary' => 'ascii',
                        'x_blob' => 'ascii',
                        'x_object' => serialize((object) ['foo' => 'ascii']),
                        'x_simple_array' => 'asc,ii',
                    ],
                    [
                        'x_string' => 'ütf-🎱',
                        'x_array' => serialize(['ütf-🎱']),
                        'x_ascii' => 'ütf-🎱',
                        'x_binary' => 'ütf-🎱',
                        'x_blob' => 'ütf-🎱',
                        'x_object' => serialize((object) ['foo' => 'ütf-🎱']),
                        'x_simple_array' => 'ütf,🎱',
                    ],
                    [
                        'x_string' => "\xB1N\xA5Y",
                        'x_array' => serialize(["\xB1N\xA5Y"]),
                        'x_ascii' => "\xB1N\xA5Y",
                        'x_binary' => "\xB1N\xA5Y",
                        'x_blob' => "\xB1N\xA5Y",
                        'x_object' => serialize((object) ['foo' => "\xB1N\xA5Y"]),
                        'x_simple_array' => "\xB1N\xA5Y",
                    ],
                ],
            ],
            [
                'SET FOREIGN_KEY_CHECKS = 0;',
                '-- BEGIN STRUCTURE tl_page',
                'DROP TABLE IF EXISTS `tl_page`;',
                "CREATE TABLE `tl_page` (`x_string` VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL, `x_array` LONGTEXT NOT NULL COMMENT '(DC2Type:array)', `x_ascii` VARCHAR(255) NOT NULL, `x_binary` VARBINARY(255) NOT NULL, `x_blob` LONGBLOB NOT NULL, `x_object` LONGTEXT NOT NULL COMMENT '(DC2Type:object)', `x_simple_array` LONGTEXT NOT NULL COMMENT '(DC2Type:simple_array)') DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB;",
                '-- BEGIN DATA tl_page',
                'INSERT INTO `tl_page` (`x_string`, `x_array`, `x_ascii`, `x_binary`, `x_blob`, `x_object`, `x_simple_array`) VALUES (\'ascii\', \'a:1:{i:0;s:5:"ascii";}\', \'ascii\', \'ascii\', \'ascii\', \'O:8:"stdClass":1:{s:3:"foo";s:5:"ascii";}\', \'asc,ii\');',
                'INSERT INTO `tl_page` (`x_string`, `x_array`, `x_ascii`, `x_binary`, `x_blob`, `x_object`, `x_simple_array`) VALUES (\'ütf-🎱\', 0x613a313a7b693a303b733a393a22c3bc74662df09f8eb1223b7d, 0xc3bc74662df09f8eb1, 0xc3bc74662df09f8eb1, 0xc3bc74662df09f8eb1, 0x4f3a383a22737464436c617373223a313a7b733a333a22666f6f223b733a393a22c3bc74662df09f8eb1223b7d, 0xc3bc74662cf09f8eb1);',
                'INSERT INTO `tl_page` (`x_string`, `x_array`, `x_ascii`, `x_binary`, `x_blob`, `x_object`, `x_simple_array`) VALUES (0xb14ea559, 0x613a313a7b693a303b733a343a22b14ea559223b7d, 0xb14ea559, 0xb14ea559, 0xb14ea559, 0x4f3a383a22737464436c617373223a313a7b733a333a22666f6f223b733a343a22b14ea559223b7d, 0xb14ea559);',
                'SET FOREIGN_KEY_CHECKS = 1;',
            ],
        ];
    }

    /**
     * @param array $tables<Table>
     * @param array $views<View>
     *
     * @return Connection&MockObject
     */
    private function mockConnection(array $tables, array $views, array $queries): Connection
    {
        $schemaManager = $this->createMock(AbstractSchemaManager::class);
        $schemaManager
            ->expects($this->once())
            ->method('listTables')
            ->willReturn($tables)
        ;

        $schemaManager
            ->expects($this->once())
            ->method('listViews')
            ->willReturn($views)
        ;

        $connection = $this->createMock(Connection::class);
        $connection
            ->expects($this->once())
            ->method('createSchemaManager')
            ->willReturn($schemaManager)
        ;

        $connection
            ->method('getDatabasePlatform')
            ->willReturn(new MySQLPlatform())
        ;

        $calls = [];
        $returns = [];

        foreach ($queries as $query => $results) {
            $calls[] = [$query];
            $returns[] = new Result(new ArrayResult($results), $connection);
        }

        $connection
            ->expects($this->exactly(\count($queries)))
            ->method('executeQuery')
            ->withConsecutive(...$calls)
            ->willReturnOnConsecutiveCalls(...$returns)
        ;

        $connection
            ->method('quote')
            ->willReturnCallback(static fn ($value) => sprintf("'%s'", str_replace("'", "''", $value)))
        ;

        return $connection;
    }
}
