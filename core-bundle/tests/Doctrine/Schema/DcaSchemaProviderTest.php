<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2016 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Test\Doctrine\Schema;

use Contao\CoreBundle\Doctrine\Schema\DcaSchemaProvider;
use Contao\CoreBundle\Test\TestCase;
use Doctrine\DBAL\Platforms\MySqlPlatform;
use Symfony\Component\DependencyInjection\Container;

/**
 * Tests the DcaSchemaProvider class.
 *
 * @author Andreas Schempp <https://github.com/aschempp>
 */
class DcaSchemaProviderTest extends TestCase
{
    /**
     * Tests the object instantiation.
     */
    public function testInstantiation()
    {
        $provider = new DcaSchemaProvider($this->getMock('Symfony\Component\DependencyInjection\ContainerInterface'));

        $this->assertInstanceOf('Contao\CoreBundle\Doctrine\Schema\DcaSchemaProvider', $provider);
    }

    /**
     * Tests that the schema is empty.
     */
    public function testEmptySchema()
    {
        $this->assertCount(0, $this->getProvider()->createSchema()->getTableNames());
    }

    /**
     * Tests creating a schema.
     *
     * @param array $dca
     * @param array $sql
     *
     * @dataProvider createSchemaProvider
     */
    public function testCreateSchema(array $dca = [], array $sql = [])
    {
        $schema = $this->getProvider($dca, $sql)->createSchema();

        $this->assertCount(1, $schema->getTableNames());
        $this->assertTrue($schema->hasTable('tl_member'));

        $table = $schema->getTable('tl_member');

        $this->assertTrue($table->hasColumn('id'));
        $this->assertEquals('integer', $table->getColumn('id')->getType()->getName());
        $this->assertEquals(true, $table->getColumn('id')->getNotnull());
        $this->assertEquals(0, $table->getColumn('id')->getDefault());
        $this->assertEquals(false, $table->getColumn('id')->getFixed());

        $this->assertTrue($table->hasColumn('pid'));
        $this->assertEquals('integer', $table->getColumn('pid')->getType()->getName());
        $this->assertEquals(false, $table->getColumn('pid')->getNotnull());
        $this->assertEquals(false, $table->getColumn('pid')->getFixed());

        $this->assertTrue($table->hasColumn('title'));
        $this->assertEquals('string', $table->getColumn('title')->getType()->getName());
        $this->assertEquals(true, $table->getColumn('title')->getNotnull());
        $this->assertEquals('', $table->getColumn('title')->getDefault());
        $this->assertEquals(false, $table->getColumn('title')->getFixed());
        $this->assertEquals(128, $table->getColumn('title')->getLength());

        $this->assertTrue($table->hasColumn('teaser'));
        $this->assertEquals('text', $table->getColumn('teaser')->getType()->getName());
        $this->assertEquals(false, $table->getColumn('teaser')->getNotnull());
        $this->assertEquals(false, $table->getColumn('teaser')->getFixed());
        $this->assertEquals(MySqlPlatform::LENGTH_LIMIT_TINYTEXT, $table->getColumn('teaser')->getLength());

        $this->assertTrue($table->hasColumn('description'));
        $this->assertEquals('text', $table->getColumn('description')->getType()->getName());
        $this->assertEquals(false, $table->getColumn('description')->getNotnull());
        $this->assertEquals(false, $table->getColumn('description')->getFixed());
        $this->assertEquals(MySqlPlatform::LENGTH_LIMIT_TEXT, $table->getColumn('description')->getLength());

        $this->assertTrue($table->hasColumn('content'));
        $this->assertEquals('text', $table->getColumn('content')->getType()->getName());
        $this->assertEquals(false, $table->getColumn('content')->getNotnull());
        $this->assertEquals(false, $table->getColumn('content')->getFixed());
        $this->assertEquals(MySqlPlatform::LENGTH_LIMIT_MEDIUMTEXT, $table->getColumn('content')->getLength());

        $this->assertTrue($table->hasColumn('price'));
        $this->assertEquals('decimal', $table->getColumn('price')->getType()->getName());
        $this->assertEquals(true, $table->getColumn('price')->getNotnull());
        $this->assertEquals('0.00', $table->getColumn('price')->getDefault());
        $this->assertEquals(false, $table->getColumn('price')->getFixed());
        $this->assertEquals(6, $table->getColumn('price')->getPrecision());
        $this->assertEquals(2, $table->getColumn('price')->getScale());

        $this->assertTrue($table->hasColumn('thumb'));
        $this->assertEquals('blob', $table->getColumn('thumb')->getType()->getName());
        $this->assertEquals(false, $table->getColumn('thumb')->getNotnull());
        $this->assertEquals(false, $table->getColumn('thumb')->getFixed());
        $this->assertEquals(MySqlPlatform::LENGTH_LIMIT_TINYBLOB, $table->getColumn('thumb')->getLength());

        $this->assertTrue($table->hasColumn('image'));
        $this->assertEquals('blob', $table->getColumn('image')->getType()->getName());
        $this->assertEquals(false, $table->getColumn('image')->getNotnull());
        $this->assertEquals(false, $table->getColumn('image')->getFixed());
        $this->assertEquals(MySqlPlatform::LENGTH_LIMIT_BLOB, $table->getColumn('image')->getLength());

        $this->assertTrue($table->hasColumn('attachment'));
        $this->assertEquals('blob', $table->getColumn('attachment')->getType()->getName());
        $this->assertEquals(false, $table->getColumn('attachment')->getNotnull());
        $this->assertEquals(false, $table->getColumn('attachment')->getFixed());
        $this->assertEquals(MySqlPlatform::LENGTH_LIMIT_MEDIUMBLOB, $table->getColumn('attachment')->getLength());

        $this->assertTrue($table->hasColumn('published'));
        $this->assertEquals('string', $table->getColumn('published')->getType()->getName());
        $this->assertEquals(true, $table->getColumn('published')->getNotnull());
        $this->assertEquals('', $table->getColumn('title')->getDefault());
        $this->assertEquals(true, $table->getColumn('published')->getFixed());
    }

    /**
     * Provides the data for the schema test.
     *
     * @return array
     */
    public function createSchemaProvider()
    {
        return [

            // Test table fields SQL string from DCA file
            [
                [
                    'tl_member' => [
                        'TABLE_FIELDS' => [
                            'id' => "`id` int(10) NOT NULL default '0'",
                            'pid' => '`pid` int(10) NULL',
                            'title' => "`title` varchar(128) NOT NULL default ''",
                            'teaser' => '`teaser` tinytext NULL',
                            'description' => '`description` text NULL',
                            'content' => '`content` mediumtext NULL',
                            'price' => "`price` decimal(6,2) NOT NULL default '0.00'",
                            'thumb' => '`thumb` tinyblob NULL',
                            'image' => '`image` blob NULL',
                            'attachment' => '`attachment` mediumblob NULL',
                            'published' => "`published` char(1) NOT NULL default ''",
                        ],
                    ],
                ],
            ],

            // Test schema definition from DCA file
            [
                [
                    'tl_member' => [
                        'SCHEMA_FIELDS' => [
                            ['name' => 'id', 'type' => 'integer'],
                            ['name' => 'pid', 'type' => 'integer', 'notnull' => false],
                            ['name' => 'title', 'type' => 'string', 'length' => 128],
                            ['name' => 'teaser', 'type' => 'text', 'notnull' => false, 'length' => MySqlPlatform::LENGTH_LIMIT_TINYTEXT],
                            ['name' => 'description', 'type' => 'text', 'notnull' => false, 'length' => MySqlPlatform::LENGTH_LIMIT_TEXT],
                            ['name' => 'content', 'type' => 'text', 'notnull' => false, 'length' => MySqlPlatform::LENGTH_LIMIT_MEDIUMTEXT],
                            ['name' => 'price', 'type' => 'decimal', 'precision' => 6, 'scale' => 2, 'default' => '0.00'],
                            ['name' => 'thumb', 'type' => 'blob', 'notnull' => false, 'length' => MySqlPlatform::LENGTH_LIMIT_TINYBLOB],
                            ['name' => 'image', 'type' => 'blob', 'notnull' => false, 'length' => MySqlPlatform::LENGTH_LIMIT_BLOB],
                            ['name' => 'attachment', 'type' => 'blob', 'notnull' => false, 'length' => MySqlPlatform::LENGTH_LIMIT_MEDIUMBLOB],
                            ['name' => 'published', 'type' => 'string', 'fixed' => true, 'length' => 1],
                        ],
                    ],
                ],
            ],

            // Test table fields from database.sql file
            [
                [],
                [
                    'tl_member' => [
                        'TABLE_FIELDS' => [
                            'id' => "`id` int(10) NOT NULL default '0'",
                            'pid' => '`pid` int(10) NULL',
                            'title' => "`title` varchar(128) NOT NULL default ''",
                            'teaser' => '`teaser` tinytext NULL',
                            'description' => '`description` text NULL',
                            'content' => '`content` mediumtext NULL',
                            'price' => "`price` decimal(6,2) NOT NULL default '0.00'",
                            'thumb' => '`thumb` tinyblob NULL',
                            'image' => '`image` blob NULL',
                            'attachment' => '`attachment` mediumblob NULL',
                            'published' => "`published` char(1) NOT NULL default ''",
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * Test the table options.
     */
    public function testTableOptions()
    {
        $provider = $this->getProvider(['tl_member' => ['TABLE_OPTIONS' => 'ENGINE=MyISAM DEFAULT CHARSET=utf8']]);
        $schema = $provider->createSchema();

        $this->assertCount(1, $schema->getTableNames());
        $this->assertTrue($schema->hasTable('tl_member'));

        $this->assertEquals('MyISAM', $schema->getTable('tl_member')->getOption('engine'));
        $this->assertEquals('utf8', $schema->getTable('tl_member')->getOption('charset'));

        $provider = $this->getProvider([], ['tl_member' => ['TABLE_OPTIONS' => 'ENGINE=MyISAM DEFAULT CHARSET=utf8']]);
        $schema = $provider->createSchema();

        $this->assertCount(1, $schema->getTableNames());
        $this->assertTrue($schema->hasTable('tl_member'));

        $this->assertEquals('MyISAM', $schema->getTable('tl_member')->getOption('engine'));
        $this->assertEquals('utf8', $schema->getTable('tl_member')->getOption('charset'));

        $provider = $this->getProvider(['tl_member' => ['TABLE_OPTIONS' => 'ENGINE=InnoDB DEFAULT CHARSET=Latin1']]);
        $schema = $provider->createSchema();

        $this->assertCount(1, $schema->getTableNames());
        $this->assertTrue($schema->hasTable('tl_member'));

        $this->assertEquals('InnoDB', $schema->getTable('tl_member')->getOption('engine'));
        $this->assertEquals('Latin1', $schema->getTable('tl_member')->getOption('charset'));
    }

    /**
     * Tests the table create definitions.
     */
    public function testTableCreateDefinitions()
    {
        $provider = $this->getProvider(
            [
                'tl_member' => [
                    'TABLE_FIELDS' => [
                        'id' => "`id` int(10) NOT NULL default '0'",
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
            ]
        );

        $schema = $provider->createSchema();

        $this->assertCount(1, $schema->getTableNames());
        $this->assertTrue($schema->hasTable('tl_member'));

        $table = $schema->getTable('tl_member');

        $this->assertTrue($table->hasIndex('PRIMARY'));
        $this->assertTrue($table->getIndex('PRIMARY')->isPrimary());
        $this->assertEquals(['id'], $table->getIndex('PRIMARY')->getColumns());

        $this->assertTrue($table->hasIndex('pid'));
        $this->assertFalse($table->getIndex('pid')->isUnique());
        $this->assertEquals(['pid'], $table->getIndex('pid')->getColumns());

        $this->assertTrue($table->hasIndex('username'));
        $this->assertTrue($table->getIndex('username')->isUnique());
        $this->assertEquals(['username'], $table->getIndex('username')->getColumns());

        $this->assertTrue($table->hasIndex('name'));
        $this->assertFalse($table->getIndex('name')->isUnique());
        $this->assertEquals(['firstname', 'lastname'], $table->getIndex('name')->getColumns());
    }

    /**
     * Tests adding an index with a key length.
     */
    public function testIndexWithKeyLength()
    {
        $provider = $this->getProvider(
            [
                'tl_files' => [
                    'TABLE_FIELDS' => [
                        'path' => "`path` varchar(1022) NOT NULL default ''",
                    ],
                    'TABLE_CREATE_DEFINITIONS' => [
                        'path' => 'KEY `path` (`path`(333))',
                    ],
                ],
            ]
        );

        $schema = $provider->createSchema();

        $this->assertCount(1, $schema->getTableNames());
        $this->assertTrue($schema->hasTable('tl_files'));

        $table = $schema->getTable('tl_files');

        $this->assertTrue($table->hasColumn('path'));
        $this->assertEquals('string', $table->getColumn('path')->getType()->getName());
        $this->assertEquals(1022, $table->getColumn('path')->getLength());

        $this->assertTrue($table->hasIndex('path'));
        $this->assertFalse($table->getIndex('path')->isUnique());
        $this->assertEquals(['path(333)'], $table->getIndex('path')->getColumns());
    }

    /**
     * Tests adding a fulltext index.
     */
    public function testFulltextIndex()
    {
        $provider = $this->getProvider(
            [
                'tl_search' => [
                    'TABLE_FIELDS' => [
                        'text' => '`text` mediumtext NULL',
                    ],
                    'TABLE_CREATE_DEFINITIONS' => [
                        'text' => 'FULLTEXT KEY `text` (`text`)',
                    ],
                ],
            ]
        );

        $schema = $provider->createSchema();

        $this->assertCount(1, $schema->getTableNames());
        $this->assertTrue($schema->hasTable('tl_search'));

        $table = $schema->getTable('tl_search');

        $this->assertTrue($table->hasColumn('text'));
        $this->assertEquals('text', $table->getColumn('text')->getType()->getName());
        $this->assertEquals(false, $table->getColumn('text')->getNotnull());
        $this->assertEquals(false, $table->getColumn('text')->getFixed());
        $this->assertEquals(MySqlPlatform::LENGTH_LIMIT_MEDIUMTEXT, $table->getColumn('text')->getLength());

        $this->assertTrue($table->hasIndex('text'));
        $this->assertFalse($table->getIndex('text')->isUnique());
        $this->assertEquals(['fulltext'], $table->getIndex('text')->getFlags());
    }

    /**
     * Tests parsing an invalid primary key.
     *
     * @expectedException \RuntimeException
     */
    public function testInvalidPrimaryKey()
    {
        $provider = $this->getProvider(
            [
                'tl_member' => [
                    'TABLE_FIELDS' => [
                        'id' => "`id` int(10) NOT NULL default '0'",
                    ],
                    'TABLE_CREATE_DEFINITIONS' => [
                        'PRIMARY' => 'PRIMARY KEY (id)',
                    ],
                ],
            ]
        );

        $provider->createSchema();
    }

    /**
     * Tests parsing an invalid key.
     *
     * @expectedException \RuntimeException
     */
    public function testInvalidKey()
    {
        $provider = $this->getProvider(
            [
                'tl_files' => [
                    'TABLE_FIELDS' => [
                        'path' => "`path` varchar(1022) NOT NULL default ''",
                    ],
                    'TABLE_CREATE_DEFINITIONS' => [
                        'path' => 'KEY path (path)',
                    ],
                ],
            ]
        );

        $provider->createSchema();
    }

    /**
     * Returns a DCA schema provider.
     *
     * @param array $dca
     * @param array $file
     *
     * @return DcaSchemaProvider
     */
    protected function getProvider(array $dca = [], array $file = [])
    {
        return new DcaSchemaProvider(
            $this->mockContainerWithDatabaseInstaller($dca, $file)
        );
    }

    /**
     * Returns a container with database installer.
     *
     * @param array $dca
     * @param array $file
     *
     * @return Container|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function mockContainerWithDatabaseInstaller(array $dca = [], array $file = [])
    {
        $connection = $this->getMock('Doctrine\DBAL\Connection', ['getDatabasePlatform'], [], '', false);
        $connection->expects($this->any())->method('getDatabasePlatform')->willReturn(new MySqlPlatform());

        $installer = $this->getMock('Contao\Database\Installer', ['getFromDca', 'getFromFile']);
        $installer->expects($this->any())->method('getFromDca')->willReturn($dca);
        $installer->expects($this->any())->method('getFromFile')->willReturn($file);

        $container = $this->mockContainerWithContaoScopes();

        $container->set(
            'contao.framework',
            $this->mockContaoFramework(
                null,
                null,
                [],
                ['Contao\Database\Installer' => $installer]
            )
        );

        $container->set('database_connection', $connection);

        return $container;
    }
}
