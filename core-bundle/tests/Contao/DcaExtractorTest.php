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

use Contao\Config;
use Contao\CoreBundle\Config\ResourceFinder;
use Contao\CoreBundle\Tests\TestCase;
use Contao\DcaExtractor;
use Contao\DcaLoader;
use Contao\System;
use Doctrine\DBAL\Connection;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Filesystem\Filesystem;
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

        $fixturesDir = $this->getFixturesDir();
        $finder = new ResourceFinder(Path::join($fixturesDir, 'vendor/contao/test-bundle/Resources/contao'));
        $locator = new FileLocator(Path::join($fixturesDir, 'vendor/contao/test-bundle/Resources/contao'));

        $container = $this->getContainerWithContaoConfiguration($fixturesDir);
        $container->set('database_connection', $connection);
        $container->set('contao.resource_finder', $finder);
        $container->set('contao.resource_locator', $locator);

        System::setContainer($container);
    }

    protected function tearDown(): void
    {
        unset($GLOBALS['TL_MIME'], $GLOBALS['TL_TEST'], $GLOBALS['TL_LANG'], $GLOBALS['TL_DCA']);

        $this->resetStaticProperties([System::class, DcaExtractor::class, Config::class, DcaLoader::class]);

        parent::tearDown();

        (new Filesystem())->remove($this->getTempDir());
    }

    public function testDoesCreateTableWithSqlConfig(): void
    {
        $extractor = DcaExtractor::getInstance('tl_test_with_sql_config');

        $this->assertTrue(isset($GLOBALS['TL_DCA']['tl_test_with_sql_config']));
        $this->assertTrue($extractor->isDbTable());
        $this->assertSame($extractor->getKeys(), ['id' => 'primary']);
        $this->assertSame($extractor->getFields(), ['id' => 'int(10) unsigned NOT NULL auto_increment']);
    }

    public function testDoesNotCreateTableWithoutSqlConfig(): void
    {
        $extractor = DcaExtractor::getInstance('tl_test_without_sql_config');

        $this->assertTrue(isset($GLOBALS['TL_DCA']['tl_test_without_sql_config']));
        $this->assertFalse($extractor->isDbTable());
        $this->assertSame($extractor->getKeys(), []);
        $this->assertSame($extractor->getFields(), []);
    }

    public function testDoesCreateTableWithSqlConfigWithoutDriver(): void
    {
        $extractor = DcaExtractor::getInstance('tl_test_with_sql_config_without_driver');

        $this->assertTrue(isset($GLOBALS['TL_DCA']['tl_test_with_sql_config_without_driver']));
        $this->assertTrue($extractor->isDbTable());
        $this->assertSame($extractor->getKeys(), ['id' => 'primary']);
        $this->assertSame($extractor->getFields(), ['id' => 'int(10) unsigned NOT NULL auto_increment']);
    }

    public function testDoesNotCreateTableWithFileDriver(): void
    {
        $extractor = DcaExtractor::getInstance('tl_test_with_file_driver');

        $this->assertTrue(isset($GLOBALS['TL_DCA']['tl_test_with_file_driver']));
        $this->assertFalse($extractor->isDbTable());
        $this->assertSame($extractor->getKeys(), []);
        $this->assertSame($extractor->getFields(), []);
    }

    public function testDoesCreateTableWithDatabaseAssistedFolderDriver(): void
    {
        $extractor = DcaExtractor::getInstance('tl_test_with_database_assisted_folder_driver');

        $this->assertTrue(isset($GLOBALS['TL_DCA']['tl_test_with_database_assisted_folder_driver']));
        $this->assertTrue($extractor->isDbTable());
        $this->assertSame($extractor->getKeys(), ['id' => 'primary']);
        $this->assertSame($extractor->getFields(), ['id' => 'int(10) unsigned NOT NULL auto_increment']);
    }

    public function testDoesNotCreateTableWithNonDatabaseAssistedFolderDriver(): void
    {
        $extractor = DcaExtractor::getInstance('tl_test_with_non_database_assisted_folder_driver');

        $this->assertTrue(isset($GLOBALS['TL_DCA']['tl_test_with_non_database_assisted_folder_driver']));
        $this->assertFalse($extractor->isDbTable());
        $this->assertSame($extractor->getKeys(), []);
        $this->assertSame($extractor->getFields(), []);
    }
}
