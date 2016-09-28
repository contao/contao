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
use Doctrine\DBAL\Schema\Schema;

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

    public function testEmptySchema()
    {
        $provider = $this->getProvider();

        $schema = $provider->createSchema();

        $this->assertCount(0, $schema->getTableNames());
    }

    public function testTableFields()
    {
        $provider = $this->getProvider(
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
                    ]
                ]
            ]
        );

        $this->assertSchema($provider->createSchema());
    }

    public function testDatabaseFile()
    {
        $provider = $this->getProvider(
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
                    'TABLE_OPTIONS' => 'ENGINE=MyISAM DEFAULT CHARSET=utf8'
                ]
            ]
        );

        $schema = $provider->createSchema();

        $this->assertSchema($schema);

        $this->assertEquals('MyISAM', $schema->getTable('tl_member')->getOption('engine'));
        $this->assertEquals('utf8', $schema->getTable('tl_member')->getOption('charset'));
    }

    public function testSchemaFields()
    {
        $provider = $this->getProvider(
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
                    ]
                ]
            ]
        );

        $this->assertSchema($provider->createSchema());
    }

    public function testTableOptions()
    {
        $provider = $this->getProvider(['tl_member' => ['TABLE_OPTIONS' => 'ENGINE=MyISAM DEFAULT CHARSET=utf8']]);

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

    private function assertSchema(Schema $schema)
    {
        $this->assertCount(1, $schema->getTableNames());
        $this->assertTrue($schema->hasTable('tl_member'));

        $this->assertTrue($schema->getTable('tl_member')->hasColumn('id'));
        $this->assertEquals('integer', $schema->getTable('tl_member')->getColumn('id')->getType()->getName());
        $this->assertEquals(true, $schema->getTable('tl_member')->getColumn('id')->getNotnull());
        $this->assertEquals(0, $schema->getTable('tl_member')->getColumn('id')->getDefault());
        $this->assertEquals(false, $schema->getTable('tl_member')->getColumn('id')->getFixed());

        $this->assertTrue($schema->getTable('tl_member')->hasColumn('pid'));
        $this->assertEquals('integer', $schema->getTable('tl_member')->getColumn('pid')->getType()->getName());
        $this->assertEquals(false, $schema->getTable('tl_member')->getColumn('pid')->getNotnull());
        $this->assertEquals(false, $schema->getTable('tl_member')->getColumn('pid')->getFixed());

        $this->assertTrue($schema->getTable('tl_member')->hasColumn('title'));
        $this->assertEquals('string', $schema->getTable('tl_member')->getColumn('title')->getType()->getName());
        $this->assertEquals(true, $schema->getTable('tl_member')->getColumn('title')->getNotnull());
        $this->assertEquals('', $schema->getTable('tl_member')->getColumn('title')->getDefault());
        $this->assertEquals(false, $schema->getTable('tl_member')->getColumn('title')->getFixed());
        $this->assertEquals(128, $schema->getTable('tl_member')->getColumn('title')->getLength());

        $this->assertTrue($schema->getTable('tl_member')->hasColumn('teaser'));
        $this->assertEquals('text', $schema->getTable('tl_member')->getColumn('teaser')->getType()->getName());
        $this->assertEquals(false, $schema->getTable('tl_member')->getColumn('teaser')->getNotnull());
        $this->assertEquals(false, $schema->getTable('tl_member')->getColumn('teaser')->getFixed());
        $this->assertEquals(MySqlPlatform::LENGTH_LIMIT_TINYTEXT, $schema->getTable('tl_member')->getColumn('teaser')->getLength());

        $this->assertTrue($schema->getTable('tl_member')->hasColumn('description'));
        $this->assertEquals('text', $schema->getTable('tl_member')->getColumn('description')->getType()->getName());
        $this->assertEquals(false, $schema->getTable('tl_member')->getColumn('description')->getNotnull());
        $this->assertEquals(false, $schema->getTable('tl_member')->getColumn('description')->getFixed());
        $this->assertEquals(MySqlPlatform::LENGTH_LIMIT_TEXT, $schema->getTable('tl_member')->getColumn('description')->getLength());

        $this->assertTrue($schema->getTable('tl_member')->hasColumn('content'));
        $this->assertEquals('text', $schema->getTable('tl_member')->getColumn('content')->getType()->getName());
        $this->assertEquals(false, $schema->getTable('tl_member')->getColumn('content')->getNotnull());
        $this->assertEquals(false, $schema->getTable('tl_member')->getColumn('content')->getFixed());
        $this->assertEquals(MySqlPlatform::LENGTH_LIMIT_MEDIUMTEXT, $schema->getTable('tl_member')->getColumn('content')->getLength());


        $this->assertTrue($schema->getTable('tl_member')->hasColumn('price'));
        $this->assertEquals('decimal', $schema->getTable('tl_member')->getColumn('price')->getType()->getName());
        $this->assertEquals(true, $schema->getTable('tl_member')->getColumn('price')->getNotnull());
        $this->assertEquals('0.00', $schema->getTable('tl_member')->getColumn('price')->getDefault());
        $this->assertEquals(false, $schema->getTable('tl_member')->getColumn('price')->getFixed());
        $this->assertEquals(6, $schema->getTable('tl_member')->getColumn('price')->getPrecision());
        $this->assertEquals(2, $schema->getTable('tl_member')->getColumn('price')->getScale());

        $this->assertTrue($schema->getTable('tl_member')->hasColumn('thumb'));
        $this->assertEquals('blob', $schema->getTable('tl_member')->getColumn('thumb')->getType()->getName());
        $this->assertEquals(false, $schema->getTable('tl_member')->getColumn('thumb')->getNotnull());
        $this->assertEquals(false, $schema->getTable('tl_member')->getColumn('thumb')->getFixed());
        $this->assertEquals(MySqlPlatform::LENGTH_LIMIT_TINYBLOB, $schema->getTable('tl_member')->getColumn('thumb')->getLength());

        $this->assertTrue($schema->getTable('tl_member')->hasColumn('image'));
        $this->assertEquals('blob', $schema->getTable('tl_member')->getColumn('image')->getType()->getName());
        $this->assertEquals(false, $schema->getTable('tl_member')->getColumn('image')->getNotnull());
        $this->assertEquals(false, $schema->getTable('tl_member')->getColumn('image')->getFixed());
        $this->assertEquals(MySqlPlatform::LENGTH_LIMIT_BLOB, $schema->getTable('tl_member')->getColumn('image')->getLength());

        $this->assertTrue($schema->getTable('tl_member')->hasColumn('attachment'));
        $this->assertEquals('blob', $schema->getTable('tl_member')->getColumn('attachment')->getType()->getName());
        $this->assertEquals(false, $schema->getTable('tl_member')->getColumn('attachment')->getNotnull());
        $this->assertEquals(false, $schema->getTable('tl_member')->getColumn('attachment')->getFixed());
        $this->assertEquals(MySqlPlatform::LENGTH_LIMIT_MEDIUMBLOB, $schema->getTable('tl_member')->getColumn('attachment')->getLength());

        $this->assertTrue($schema->getTable('tl_member')->hasColumn('published'));
        $this->assertEquals('string', $schema->getTable('tl_member')->getColumn('published')->getType()->getName());
        $this->assertEquals(true, $schema->getTable('tl_member')->getColumn('published')->getNotnull());
        $this->assertEquals('', $schema->getTable('tl_member')->getColumn('title')->getDefault());
        $this->assertEquals(true, $schema->getTable('tl_member')->getColumn('published')->getFixed());
    }

    private function getProvider(array $dca = [], array $file = [])
    {
        $connection = $this->getMock('Doctrine\DBAL\Connection', ['getDatabasePlatform'], [], '', false);
        $connection->method('getDatabasePlatform')->willReturn(new MySqlPlatform());

        $container = $this->mockContainerWithContaoScopes();

        $container->set(
            'contao.framework',
            $this->mockContaoFramework(
                null,
                null,
                [],
                ['Contao\Database\Installer' => $this->mockInstaller($dca, $file)]
            )
        );

        $container->set('database_connection', $connection);

        return new DcaSchemaProvider($container);
    }

    private function mockInstaller(array $dca = [], array $file = [])
    {
        $installer = $this->getMock('Contao\Database\Installer', ['getFromDca', 'getFromFile']);

        $installer->method('getFromDca')->willReturn($dca);
        $installer->method('getFromFile')->willReturn($file);

        return $installer;
    }
}
