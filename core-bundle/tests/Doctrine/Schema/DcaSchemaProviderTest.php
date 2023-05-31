<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Doctrine\Schema;

use Contao\CoreBundle\Tests\Doctrine\DoctrineTestCase;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Platforms\AbstractMySQLPlatform;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Schema\Table;
use Doctrine\ORM\Mapping\Builder\ClassMetadataBuilder;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Tools\SchemaTool;

class DcaSchemaProviderTest extends DoctrineTestCase
{
    /**
     * @dataProvider provideDefinitions
     */
    public function testAppendToSchema(array $dca = []): void
    {
        $schema = $this->getSchema();
        $this->getDcaSchemaProvider($dca)->appendToSchema($schema);
        $table = $schema->getTable('tl_member');

        $this->assertTrue($table->hasColumn('id'));
        $this->assertSame('integer', $table->getColumn('id')->getType()->getName());
        $this->assertTrue($table->getColumn('id')->getNotnull());
        $this->assertFalse($table->getColumn('id')->getFixed());

        /** @var int|null $idDefault */
        $idDefault = $table->getColumn('id')->getDefault();

        if (null !== $idDefault) {
            $this->assertSame(0, $idDefault);
        }

        $this->assertTrue($table->hasColumn('pid'));
        $this->assertSame('integer', $table->getColumn('pid')->getType()->getName());
        $this->assertFalse($table->getColumn('pid')->getNotnull());
        $this->assertFalse($table->getColumn('pid')->getFixed());

        $this->assertTrue($table->hasColumn('title'));
        $this->assertSame('string', $table->getColumn('title')->getType()->getName());
        $this->assertTrue($table->getColumn('title')->getNotnull());
        $this->assertFalse($table->getColumn('title')->getFixed());
        $this->assertSame(128, $table->getColumn('title')->getLength());
        $this->assertSame('utf8mb4_bin', $table->getColumn('title')->getPlatformOption('collation'));

        $titleDefault = $table->getColumn('title')->getDefault();

        if (null !== $titleDefault) {
            $this->assertSame('', $titleDefault);
        }

        $this->assertTrue($table->hasColumn('uppercase'));
        $this->assertSame('string', $table->getColumn('uppercase')->getType()->getName());
        $this->assertTrue($table->getColumn('uppercase')->getNotnull());
        $this->assertFalse($table->getColumn('uppercase')->getFixed());
        $this->assertSame(64, $table->getColumn('uppercase')->getLength());
        $this->assertSame('1.00', $table->getColumn('uppercase')->getDefault());

        $this->assertTrue($table->hasColumn('teaser'));
        $this->assertSame('text', $table->getColumn('teaser')->getType()->getName());
        $this->assertFalse($table->getColumn('teaser')->getNotnull());
        $this->assertFalse($table->getColumn('teaser')->getFixed());
        $this->assertSame(AbstractMySQLPlatform::LENGTH_LIMIT_TINYTEXT, $table->getColumn('teaser')->getLength());

        $this->assertTrue($table->hasColumn('description'));
        $this->assertSame('text', $table->getColumn('description')->getType()->getName());
        $this->assertFalse($table->getColumn('description')->getNotnull());
        $this->assertFalse($table->getColumn('description')->getFixed());
        $this->assertSame(AbstractMySQLPlatform::LENGTH_LIMIT_TEXT, $table->getColumn('description')->getLength());

        $this->assertTrue($table->hasColumn('content'));
        $this->assertSame('text', $table->getColumn('content')->getType()->getName());
        $this->assertFalse($table->getColumn('content')->getNotnull());
        $this->assertFalse($table->getColumn('content')->getFixed());
        $this->assertSame(AbstractMySQLPlatform::LENGTH_LIMIT_MEDIUMTEXT, $table->getColumn('content')->getLength());

        $this->assertTrue($table->hasColumn('price'));
        $this->assertSame('decimal', $table->getColumn('price')->getType()->getName());
        $this->assertTrue($table->getColumn('price')->getNotnull());
        $this->assertFalse($table->getColumn('price')->getFixed());
        $this->assertSame(6, $table->getColumn('price')->getPrecision());
        $this->assertSame(2, $table->getColumn('price')->getScale());

        /** @var float|null $priceDefault */
        $priceDefault = $table->getColumn('price')->getDefault();

        if (null !== $priceDefault) {
            $this->assertSame(1.99, $priceDefault);
        }

        $this->assertTrue($table->hasColumn('thumb'));
        $this->assertSame('blob', $table->getColumn('thumb')->getType()->getName());
        $this->assertFalse($table->getColumn('thumb')->getNotnull());
        $this->assertFalse($table->getColumn('thumb')->getFixed());
        $this->assertSame(AbstractMySQLPlatform::LENGTH_LIMIT_TINYBLOB, $table->getColumn('thumb')->getLength());

        $this->assertTrue($table->hasColumn('image'));
        $this->assertSame('blob', $table->getColumn('image')->getType()->getName());
        $this->assertFalse($table->getColumn('image')->getNotnull());
        $this->assertFalse($table->getColumn('image')->getFixed());
        $this->assertSame(AbstractMySQLPlatform::LENGTH_LIMIT_BLOB, $table->getColumn('image')->getLength());

        $this->assertTrue($table->hasColumn('attachment'));
        $this->assertSame('blob', $table->getColumn('attachment')->getType()->getName());
        $this->assertFalse($table->getColumn('attachment')->getNotnull());
        $this->assertFalse($table->getColumn('attachment')->getFixed());
        $this->assertSame(AbstractMySQLPlatform::LENGTH_LIMIT_MEDIUMBLOB, $table->getColumn('attachment')->getLength());

        $this->assertTrue($table->hasColumn('published'));
        $this->assertSame('string', $table->getColumn('published')->getType()->getName());
        $this->assertTrue($table->getColumn('published')->getNotnull());
        $this->assertTrue($table->getColumn('published')->getFixed());

        $publishedDefault = $table->getColumn('published')->getDefault();

        if (null !== $publishedDefault) {
            $this->assertSame('', $publishedDefault);
        }
    }

    public function provideDefinitions(): \Generator
    {
        yield 'table fields SQL string from DCA file' => [
            [
                'tl_member' => [
                    'TABLE_FIELDS' => [
                        'id' => '`id` int(10) NOT NULL default 0',
                        'pid' => '`pid` int(10) NULL',
                        'title' => "`title` varchar(128) BINARY NOT NULL default ''",
                        'uppercase' => "`uppercase` varchar(64) NOT NULL DEFAULT '1.00'",
                        'teaser' => '`teaser` tinytext NULL',
                        'description' => '`description` text NULL',
                        'content' => '`content` mediumtext NULL',
                        'price' => '`price` decimal(6,2) NOT NULL default 1.99',
                        'thumb' => '`thumb` tinyblob NULL',
                        'image' => '`image` blob NULL',
                        'attachment' => '`attachment` mediumblob NULL',
                        'published' => "`published` char(1) NOT NULL default ''",
                    ],
                ],
            ],
        ];

        yield 'schema definition from DCA file' => [
            [
                'tl_member' => [
                    'SCHEMA_FIELDS' => [
                        ['name' => 'id', 'type' => 'integer'],
                        ['name' => 'pid', 'type' => 'integer', 'notnull' => false],
                        ['name' => 'title', 'type' => 'string', 'length' => 128, 'customSchemaOptions' => ['case_sensitive' => true]],
                        ['name' => 'uppercase', 'type' => 'string', 'length' => 64, 'default' => '1.00'],
                        ['name' => 'teaser', 'type' => 'text', 'notnull' => false, 'length' => AbstractMySQLPlatform::LENGTH_LIMIT_TINYTEXT],
                        ['name' => 'description', 'type' => 'text', 'notnull' => false, 'length' => AbstractMySQLPlatform::LENGTH_LIMIT_TEXT],
                        ['name' => 'content', 'type' => 'text', 'notnull' => false, 'length' => AbstractMySQLPlatform::LENGTH_LIMIT_MEDIUMTEXT],
                        ['name' => 'price', 'type' => 'decimal', 'precision' => 6, 'scale' => 2, 'default' => 1.99],
                        ['name' => 'thumb', 'type' => 'blob', 'notnull' => false, 'length' => AbstractMySQLPlatform::LENGTH_LIMIT_TINYBLOB],
                        ['name' => 'image', 'type' => 'blob', 'notnull' => false, 'length' => AbstractMySQLPlatform::LENGTH_LIMIT_BLOB],
                        ['name' => 'attachment', 'type' => 'blob', 'notnull' => false, 'length' => AbstractMySQLPlatform::LENGTH_LIMIT_MEDIUMBLOB],
                        ['name' => 'published', 'type' => 'string', 'fixed' => true, 'length' => 1],
                    ],
                ],
            ],
        ];
    }

    public function testAppendToSchemaHandlesSimpleFieldDefinition(): void
    {
        $dca = [
            'tl_member' => [
                'TABLE_FIELDS' => [
                    'id' => '`id` INTEGER',
                ],
            ],
        ];

        $schema = $this->getSchema();
        $this->getDcaSchemaProvider($dca)->appendToSchema($schema);
        $table = $schema->getTable('tl_member');

        $this->assertTrue($table->hasColumn('id'));
        $this->assertSame('integer', $table->getColumn('id')->getType()->getName());
        $this->assertFalse($table->getColumn('id')->getNotnull());
        $this->assertFalse($table->getColumn('id')->getFixed());
    }

    /**
     * @dataProvider provideTableOptions
     */
    public function testAppendToSchemaReadsTheTableOptions(string $options, \Closure $assertions): void
    {
        $dca = [
            'tl_member' => [
                'TABLE_OPTIONS' => $options,
            ],
        ];

        $schema = $this->getSchema();
        $this->getDcaSchemaProvider($dca)->appendToSchema($schema);
        $table = $schema->getTable('tl_member');

        $assertions($table);
    }

    public function provideTableOptions(): \Generator
    {
        yield [
            'ENGINE=InnoDB ROW_FORMAT=DYNAMIC DEFAULT CHARSET=utf8 COLLATE utf8_unicode_ci',
            function (Table $table): void {
                $this->assertSame('InnoDB', $table->getOption('engine'));
                $this->assertSame('utf8', $table->getOption('charset'));
                $this->assertSame('utf8_unicode_ci', $table->getOption('collate'));
            },
        ];

        yield [
            'ENGINE=InnoDB ROW_FORMAT=DYNAMIC DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_unicode_ci',
            function (Table $table): void {
                $this->assertSame('InnoDB', $table->getOption('engine'));
                $this->assertSame('utf8mb4', $table->getOption('charset'));
                $this->assertSame('utf8mb4_unicode_ci', $table->getOption('collate'));
            },
        ];

        yield [
            'ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE latin1_general_ci',
            function (Table $table): void {
                $this->assertSame('MyISAM', $table->getOption('engine'));
                $this->assertSame('latin1', $table->getOption('charset'));
                $this->assertSame('latin1_general_ci', $table->getOption('collate'));
                $this->assertFalse($table->hasOption('row_format'));
            },
        ];
    }

    public function testAppendToSchemaCreatesTheTableDefinitions(): void
    {
        $dca = [
            'tl_member' => [
                'TABLE_FIELDS' => [
                    'id' => '`id` int(10) NOT NULL default 0',
                    'pid' => '`pid` int(10) NULL',
                    'username' => "`username` varchar(128) NOT NULL default ''",
                    'firstname' => "`firstname` varchar(128) NOT NULL default ''",
                    'lastname' => "`lastname` varchar(128) NOT NULL default ''",
                ],
                'TABLE_CREATE_DEFINITIONS' => [
                    'PRIMARY' => 'PRIMARY KEY (`id`)',
                    'pid' => 'KEY `pid` (`pid`)',
                    'username' => 'UNIQUE KEY `username` (`username`)',
                    'name' => 'KEY `name` (`firstname`, `lastname`)',
                ],
            ],
        ];

        $schema = $this->getSchema();
        $this->getDcaSchemaProvider($dca)->appendToSchema($schema);
        $table = $schema->getTable('tl_member');

        $this->assertTrue($table->hasIndex('PRIMARY'));
        $this->assertTrue($table->getIndex('PRIMARY')->isPrimary());
        $this->assertSame(['id'], $table->getIndex('PRIMARY')->getColumns());

        $this->assertTrue($table->hasIndex('pid'));
        $this->assertFalse($table->getIndex('pid')->isUnique());
        $this->assertSame(['pid'], $table->getIndex('pid')->getColumns());

        $this->assertTrue($table->hasIndex('username'));
        $this->assertTrue($table->getIndex('username')->isUnique());
        $this->assertSame(['username'], $table->getIndex('username')->getColumns());

        $this->assertTrue($table->hasIndex('name'));
        $this->assertFalse($table->getIndex('name')->isUnique());
        $this->assertSame(['firstname', 'lastname'], $table->getIndex('name')->getColumns());
    }

    /**
     * @dataProvider provideIndexes
     */
    public function testAppendToSchemaAddsTheIndexLength(int|null $expected, string $tableOptions, bool|string|null $largePrefixes = null, string|null $version = null, string|null $filePerTable = null, string|null $fileFormat = null): void
    {
        $dca = [
            'tl_files' => [
                'TABLE_FIELDS' => [
                    'name' => "`name` varchar(255) NOT NULL default ''",
                ],
                'TABLE_CREATE_DEFINITIONS' => [
                    'name' => 'KEY `name` (`name`)',
                ],
                'TABLE_OPTIONS' => $tableOptions,
            ],
        ];

        $connection = $this->createMock(Connection::class);
        $connection
            ->method('fetchAssociative')
            ->willReturnCallback(
                static function ($query) use ($fileFormat, $filePerTable, $largePrefixes) {
                    $map = [
                        "SHOW VARIABLES LIKE 'innodb_large_prefix'" => $largePrefixes,
                        "SHOW VARIABLES LIKE 'innodb_file_per_table'" => $filePerTable,
                        "SHOW VARIABLES LIKE 'innodb_file_format'" => $fileFormat,
                    ];

                    if (\array_key_exists($query, $map)) {
                        return ['Value' => $map[$query]];
                    }

                    throw new \RuntimeException(sprintf('Test does not mirror actual query, got: "%s"', $query));
                }
            )
        ;

        $connection
            ->method('fetchOne')
            ->with('SELECT @@version')
            ->willReturn($version)
        ;

        $schema = $this->getSchema();
        $this->getDcaSchemaProvider($dca, $connection)->appendToSchema($schema);
        $table = $schema->getTable('tl_files');

        $this->assertTrue($table->hasColumn('name'));
        $this->assertSame('string', $table->getColumn('name')->getType()->getName());
        $this->assertSame(255, $table->getColumn('name')->getLength());

        $this->assertTrue($table->hasIndex('name'));
        $this->assertFalse($table->getIndex('name')->isUnique());
        $this->assertSame([$expected], $table->getIndex('name')->getOption('lengths'));

        if (method_exists(AbstractPlatform::class, 'supportsColumnLengthIndexes')) {
            $this->assertSame(['name'], $table->getIndex('name')->getColumns());
        } else {
            $column = 'name';

            if ($expected) {
                $column .= '('.$expected.')';
            }

            $this->assertSame([$column], $table->getIndex('name')->getColumns());
        }
    }

    public function provideIndexes(): \Generator
    {
        yield 'MyISAM, utf8' => [
            null,
            'ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE utf8_unicode_ci',
        ];

        yield 'MyISAM, utf8mb4' => [
            250,
            'ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_unicode_ci',
        ];

        $matrix = [
            'MySQL' => ['5.5.62', '5.7.7', '8.0.13'],
            'MariaDB' => ['10.1.37', '10.2.2', '10.3.12'],
        ];

        foreach ($matrix as $vendor => $versions) {
            yield $vendor.' '.$versions[0].', utf8, large_prefixes=Off' => [
                null,
                'ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE utf8_unicode_ci',
                'Off',
                $versions[0],
            ];

            yield $vendor.' '.$versions[0].', utf8mb4, large_prefixes=Off' => [
                191,
                'ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_unicode_ci',
                '0',
                $versions[0],
            ];

            yield $vendor.' '.$versions[0].', utf8, large_prefixes=On' => [
                null,
                'ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE utf8_unicode_ci',
                'On',
                $versions[0],
            ];

            yield $vendor.' '.$versions[0].', utf8mb4, large_prefixes=On' => [
                191,
                'ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_unicode_ci',
                '1',
                $versions[0],
            ];

            yield $vendor.' '.$versions[0].', utf8, large_prefixes=On, file_per_table=Off' => [
                null,
                'ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE utf8_unicode_ci',
                'On',
                $versions[0],
                'Off',
            ];

            yield $vendor.' '.$versions[0].', utf8mb4, large_prefixes=On, file_per_table=Off' => [
                191,
                'ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_unicode_ci',
                'On',
                $versions[0],
                '0',
            ];

            yield $vendor.' '.$versions[0].', utf8, large_prefixes=On, file_per_table=Off, file_format=Barracuda' => [
                null,
                'ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE utf8_unicode_ci',
                'On',
                $versions[0],
                'Off',
                'Barracuda',
            ];

            yield $vendor.' '.$versions[0].', utf8mb4, large_prefixes=On, file_per_table=Off, file_format=Barracuda' => [
                191,
                'ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_unicode_ci',
                'On',
                $versions[0],
                '0',
                'Barracuda',
            ];

            yield $vendor.' '.$versions[0].', utf8, large_prefixes=On, file_per_table=On' => [
                null,
                'ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE utf8_unicode_ci',
                'On',
                $versions[0],
                'On',
            ];

            yield $vendor.' '.$versions[0].', utf8mb4, large_prefixes=On, file_per_table=On' => [
                191,
                'ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_unicode_ci',
                'On',
                $versions[0],
                '1',
            ];

            yield $vendor.' '.$versions[0].', utf8, large_prefixes=On, file_per_table=On, file_format=Barracuda' => [
                null,
                'ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE utf8_unicode_ci',
                'On',
                $versions[0],
                'On',
                'Barracuda',
            ];

            yield $vendor.' '.$versions[0].', utf8mb4, large_prefixes=On, file_per_table=On, file_format=Barracuda' => [
                null,
                'ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_unicode_ci',
                'On',
                $versions[0],
                '1',
                'Barracuda',
            ];

            // innodb_large_prefixes enabled by default
            yield $vendor.' '.$versions[1].' with utf8' => [
                null,
                'ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE utf8_unicode_ci',
                null,
                $versions[1],
            ];

            yield $vendor.' '.$versions[1].' with utf8mb4' => [
                null,
                'ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_unicode_ci',
                null,
                $versions[1],
            ];

            // innodb_large_prefixes removed
            yield $vendor.' '.$versions[2].' with utf8' => [
                null,
                'ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE utf8_unicode_ci',
                null,
                $versions[2],
            ];

            yield $vendor.' '.$versions[2].' with utf8mb4' => [
                null,
                'ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_unicode_ci',
                null,
                $versions[2],
            ];
        }
    }

    public function testAppendToSchemaHandlesIndexesOverMultipleColumns(): void
    {
        $dca = [
            'tl_foo' => [
                'TABLE_FIELDS' => [
                    'col1' => "`col1` varchar(255) NOT NULL default ''",
                    'col2' => "`col2` varchar(255) NOT NULL default ''",
                    'col3' => "`col3` varchar(255) NOT NULL default ''",
                ],
                'TABLE_CREATE_DEFINITIONS' => [
                    'col123' => 'KEY `col123` (`col1`(100), `col2`, `col3`(99))',
                ],
                'TABLE_OPTIONS' => 'ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE utf8_unicode_ci',
            ],
        ];

        $schema = $this->getSchema();
        $this->getDcaSchemaProvider($dca)->appendToSchema($schema);
        $table = $schema->getTable('tl_foo');

        for ($i = 1; $i <= 3; ++$i) {
            $this->assertTrue($table->hasColumn('col'.$i));
            $this->assertSame('string', $table->getColumn('col'.$i)->getType()->getName());
            $this->assertSame(255, $table->getColumn('col'.$i)->getLength());
        }

        $this->assertTrue($table->hasIndex('col123'));
        $this->assertFalse($table->getIndex('col123')->isUnique());
        $this->assertSame([100, null, 99], $table->getIndex('col123')->getOption('lengths'));

        if (method_exists(AbstractPlatform::class, 'supportsColumnLengthIndexes')) {
            $this->assertSame(['col1', 'col2', 'col3'], $table->getIndex('col123')->getColumns());
        } else {
            $this->assertSame(['col1(100)', 'col2', 'col3(99)'], $table->getIndex('col123')->getColumns());
        }
    }

    public function testAppendToSchemaHandlesFulltextIndexes(): void
    {
        $dca = [
            'tl_search' => [
                'TABLE_FIELDS' => [
                    'text' => '`text` mediumtext NULL',
                ],
                'TABLE_CREATE_DEFINITIONS' => [
                    'text' => 'FULLTEXT KEY `text` (`text`)',
                ],
                'TABLE_OPTIONS' => 'ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_unicode_ci',
            ],
        ];

        $connection = $this->createMock(Connection::class);
        $connection
            ->method('fetchAssociative')
            ->willReturn(['Value' => null])
        ;

        $connection
            ->method('fetchOne')
            ->with('SELECT @@version')
            ->willReturn('foo')
        ;

        $schema = $this->getSchema();
        $this->getDcaSchemaProvider($dca, $connection)->appendToSchema($schema);
        $table = $schema->getTable('tl_search');

        $this->assertTrue($table->hasColumn('text'));
        $this->assertSame('text', $table->getColumn('text')->getType()->getName());
        $this->assertFalse($table->getColumn('text')->getNotnull());
        $this->assertFalse($table->getColumn('text')->getFixed());
        $this->assertSame(AbstractMySQLPlatform::LENGTH_LIMIT_MEDIUMTEXT, $table->getColumn('text')->getLength());

        $this->assertTrue($table->hasIndex('text'));
        $this->assertFalse($table->getIndex('text')->isUnique());
        $this->assertSame(['fulltext'], $table->getIndex('text')->getFlags());
    }

    /**
     * @dataProvider provideInvalidIndexDefinitions
     */
    public function testAppendToSchemaFailsIfIndexesAreInvalid(array $dca, string $expectedExceptionMessage): void
    {
        $dcaSchemaProvider = $this->getDcaSchemaProvider($dca);
        $schema = $this->getSchema();

        $this->expectExceptionMessage($expectedExceptionMessage);

        $dcaSchemaProvider->appendToSchema($schema);
    }

    public function provideInvalidIndexDefinitions(): \Generator
    {
        yield 'invalid primary key' => [
            [
                'tl_member' => [
                    'TABLE_FIELDS' => [
                        'id' => '`id` int(10) NOT NULL default 0',
                    ],
                    'TABLE_CREATE_DEFINITIONS' => [
                        'PRIMARY' => 'PRIMARY KEY (id)',
                    ],
                ],
            ],
            'Primary key definition "primary key (id)" could not be parsed.',
        ];

        yield 'invalid index' => [
            [
                'tl_files' => [
                    'TABLE_FIELDS' => [
                        'path' => "`path` varchar(1022) NOT NULL default ''",
                    ],
                    'TABLE_CREATE_DEFINITIONS' => [
                        'path' => 'KEY path (path)',
                    ],
                ],
            ],
            'Key definition "key path (path)" could not be parsed.',
        ];
    }

    public function testAppendToSchemaIgnoresExistingMetadataDefinitions(): void
    {
        $dcaMetadata = [
            'tl_page' => [
                'TABLE_FIELDS' => [
                    'id' => '`id` int(10) NOT NULL default 0',
                    'title' => "`title` varchar(128) BINARY NOT NULL default ''",
                ],
                'SCHEMA_FIELDS' => [
                    'published' => ['type' => 'string', 'fixed' => true, 'length' => 1],
                ],
                'TABLE_CREATE_DEFINITIONS' => [
                    'published' => 'KEY `title` (`published`)',
                ],
            ],
        ];

        $entityMetadata = new ClassMetadata(\stdClass::class);

        (new ClassMetadataBuilder($entityMetadata))
            ->setTable('tl_page')
            ->addField('id', 'integer')
            ->addField('published', 'boolean')
            ->addField('bar', 'string')
            ->addIndex(['published'], 'published')
        ;

        $manager = $this->getTestEntityManager();
        $schema = (new SchemaTool($manager))->getSchemaFromMetadata([$entityMetadata]);

        $provider = $this->getDcaSchemaProvider($dcaMetadata);
        $provider->appendToSchema($schema);

        $columns = $schema->getTable('tl_page')->getColumns();

        $this->assertCount(4, $columns);
        $this->assertSame('integer', $columns['id']->getType()->getName());
        $this->assertSame('boolean', $columns['published']->getType()->getName());
        $this->assertSame('string', $columns['bar']->getType()->getName());
        $this->assertSame('string', $columns['title']->getType()->getName());
    }
}
