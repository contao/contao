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

use Contao\CoreBundle\Config\ResourceFinder;
use Contao\CoreBundle\Tests\TestCase;
use Contao\DC_Table;
use Contao\DcaExtractor;
use Contao\System;
use Doctrine\DBAL\Connection;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Filesystem\Path;

class DcaExtractorTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $connection = $this->createMock(Connection::class);
        $connection
            ->method('getParams')
            ->willReturn([])
        ;

        $projectDir = $this->getFixturesDir();

        $finder = new ResourceFinder($projectDir.'/vendor/contao/test-bundle/Resources/contao');

        $container = new ContainerBuilder();
        $container->setParameter('kernel.debug', false);
        $container->setParameter('kernel.project_dir', $projectDir);
        $container->setParameter('kernel.cache_dir', Path::join($projectDir, 'var/cache'));
        $container->set('database_connection', $connection);
        $container->set('contao.resource_finder', $finder);

        System::setContainer($container);

        $GLOBALS['TL_DCA']['tl_nosqlconfig'] = [
            'config' => [
                'dataContainer' => DC_Table::class,
            ],
            'fields' => [
                'foobar' => [
                    'sql' => ['type' => 'string', 'length' => 255, 'default' => ''],
                ],
            ],
        ];

        $GLOBALS['TL_DCA']['tl_withsqlconfig'] = [
            'config' => [
                'dataContainer' => DC_Table::class,
                'sql' => [
                    'keys' => [
                        'id' => 'primary',
                    ],
                ],
            ],
            'fields' => [
                'id' => [
                    'sql' => 'int(10) unsigned NOT NULL auto_increment',
                ],
                'tstamp' => [
                    'sql' => 'int(10) unsigned NOT NULL default 0',
                ],
            ],
        ];

        $GLOBALS['TL_DCA']['tl_withsqlconfig_nodriver'] = [
            'config' => [
                'sql' => [
                    'keys' => [
                        'id' => 'primary',
                    ],
                ],
            ],
            'fields' => [
                'id' => [
                    'sql' => 'int(10) unsigned NOT NULL auto_increment',
                ],
                'tstamp' => [
                    'sql' => 'int(10) unsigned NOT NULL default 0',
                ],
            ],
        ];
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        unset($GLOBALS['TL_DCA']);
    }

    public function testDoesNotCreateTableWithoutSqlConfig(): void
    {
        $extractor = DcaExtractor::getInstance('tl_nosqlconfig');
        $this->assertSame($extractor->getKeys(), []);
        $this->assertSame($extractor->getFields(), []);
    }

    public function testDoesCreateTableWithSqlConfig(): void
    {
        $extractor = DcaExtractor::getInstance('tl_withsqlconfig');
        $this->assertSame($extractor->getKeys(), ['id' => 'primary']);
        $this->assertSame($extractor->getFields(), [
            'id' => 'int(10) unsigned NOT NULL auto_increment',
            'tstamp' => 'int(10) unsigned NOT NULL default 0',
        ]);
    }

    public function testDoesCreateTableWithSqlConfigWithoutDriver(): void
    {
        $extractor = DcaExtractor::getInstance('tl_withsqlconfig_nodriver');
        $this->assertSame($extractor->getKeys(), ['id' => 'primary']);
        $this->assertSame($extractor->getFields(), [
            'id' => 'int(10) unsigned NOT NULL auto_increment',
            'tstamp' => 'int(10) unsigned NOT NULL default 0',
        ]);
    }
}
