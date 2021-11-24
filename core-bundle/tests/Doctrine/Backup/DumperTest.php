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
        $config = (new CreateConfig($backup));

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
                'CREATE TABLE tl_page (foobar VARCHAR(255) NOT NULL) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB;',
                '-- BEGIN DATA tl_page',
                'SET FOREIGN_KEY_CHECKS = 1;',
            ],
        ];

        yield 'Table with data' => [
            [new Table('tl_page', [new Column('foobar', Type::getType(Types::STRING))])],
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
            ],
            [
                'SET FOREIGN_KEY_CHECKS = 0;',
                '-- BEGIN STRUCTURE tl_page',
                'DROP TABLE IF EXISTS `tl_page`;',
                'CREATE TABLE tl_page (foobar VARCHAR(255) NOT NULL) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB;',
                '-- BEGIN DATA tl_page',
                'INSERT INTO `tl_page` (`foobar`) VALUES (`value1`);',
                'INSERT INTO `tl_page` (`foobar`) VALUES (NULL);',
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
                'CREATE TABLE tl_page (foobar VARCHAR(255) NOT NULL) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB;',
                '-- BEGIN DATA tl_page',
                'INSERT INTO `tl_page` (`foobar`) VALUES (`value1`);',
                'INSERT INTO `tl_page` (`foobar`) VALUES (NULL);',
                '-- BEGIN STRUCTURE tl_news',
                'DROP TABLE IF EXISTS `tl_news`;',
                'CREATE TABLE tl_news (foobar VARCHAR(255) NOT NULL) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB;',
                '-- BEGIN DATA tl_news',
                'INSERT INTO `tl_news` (`foobar`) VALUES (`value1`);',
                'SET FOREIGN_KEY_CHECKS = 1;',
            ],
        ];

        yield 'Table structure and views' => [
            [new Table('tl_page', [new Column('foobar', Type::getType(Types::STRING))])],
            [new View('view_name', 'SELECT tl_page.id AS id FROM tl_page')],
            [
                'SELECT `foobar` AS `foobar` FROM `tl_page`' => [],
            ],
            [
                'SET FOREIGN_KEY_CHECKS = 0;',
                '-- BEGIN STRUCTURE tl_page',
                'DROP TABLE IF EXISTS `tl_page`;',
                'CREATE TABLE tl_page (foobar VARCHAR(255) NOT NULL) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB;',
                '-- BEGIN DATA tl_page',
                '-- BEGIN VIEW view_name',
                'CREATE VIEW view_name AS SELECT tl_page.id AS id FROM tl_page;',
                'SET FOREIGN_KEY_CHECKS = 1;',
            ],
        ];
    }

    /**
     * @param array $tables<Table>
     * @param array $views<View>
     */
    private function mockConnection(array $tables, array $views, array $queries)
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
            ->willReturnCallback(static fn ($value) => sprintf('`%s`', $value))
        ;

        return $connection;
    }
}
