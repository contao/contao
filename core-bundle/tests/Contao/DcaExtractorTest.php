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
use Contao\CoreBundle\DataContainer\InvalidConfigException;
use Contao\CoreBundle\Tests\Fixtures\Enum\IntBackedEnum;
use Contao\CoreBundle\Tests\Fixtures\Enum\StringBackedEnum;
use Contao\CoreBundle\Tests\TestCase;
use Contao\DcaExtractor;
use Contao\DcaLoader;
use Contao\System;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;

class DcaExtractorTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        System::setContainer($this->getContainerWithFixtures());
    }

    protected function tearDown(): void
    {
        unset($GLOBALS['TL_MIME'], $GLOBALS['TL_TEST'], $GLOBALS['TL_LANG'], $GLOBALS['TL_DCA']);

        $this->resetStaticProperties([System::class, DcaExtractor::class, Config::class, DcaLoader::class]);

        parent::tearDown();

        (new Filesystem())->remove([$this->getTempDir(), Path::join($this->getFixturesDir(), 'var/cache')]);
    }

    public function testDoesCreateTableWithSqlConfig(): void
    {
        $extractor = DcaExtractor::getInstance('tl_test_with_sql_config');

        $this->assertTrue(isset($GLOBALS['TL_DCA']['tl_test_with_sql_config']));
        $this->assertTrue($extractor->isDbTable());
        $this->assertSame(['id' => 'primary'], $extractor->getKeys());
        $this->assertSame(['id' => 'int(10) unsigned NOT NULL auto_increment'], $extractor->getFields());
    }

    public function testDoesNotCreateTableWithoutSqlConfig(): void
    {
        $extractor = DcaExtractor::getInstance('tl_test_without_sql_config');

        $this->assertTrue(isset($GLOBALS['TL_DCA']['tl_test_without_sql_config']));
        $this->assertFalse($extractor->isDbTable());
        $this->assertSame([], $extractor->getKeys());
        $this->assertSame([], $extractor->getFields());
    }

    public function testDoesCreateTableWithSqlConfigWithoutDriver(): void
    {
        $extractor = DcaExtractor::getInstance('tl_test_with_sql_config_without_driver');

        $this->assertTrue(isset($GLOBALS['TL_DCA']['tl_test_with_sql_config_without_driver']));
        $this->assertTrue($extractor->isDbTable());
        $this->assertSame(['id' => 'primary'], $extractor->getKeys());
        $this->assertSame(['id' => 'int(10) unsigned NOT NULL auto_increment'], $extractor->getFields());
    }

    public function testDoesNotCreateTableWithFileDriver(): void
    {
        $extractor = DcaExtractor::getInstance('tl_test_with_file_driver');

        $this->assertTrue(isset($GLOBALS['TL_DCA']['tl_test_with_file_driver']));
        $this->assertFalse($extractor->isDbTable());
        $this->assertSame([], $extractor->getKeys());
        $this->assertSame([], $extractor->getFields());
    }

    public function testDoesCreateTableWithDatabaseAssistedFolderDriver(): void
    {
        $extractor = DcaExtractor::getInstance('tl_test_with_database_assisted_folder_driver');

        $this->assertTrue(isset($GLOBALS['TL_DCA']['tl_test_with_database_assisted_folder_driver']));
        $this->assertTrue($extractor->isDbTable());
        $this->assertSame(['id' => 'primary'], $extractor->getKeys());
        $this->assertSame(['id' => 'int(10) unsigned NOT NULL auto_increment'], $extractor->getFields());
    }

    public function testDoesNotCreateTableWithNonDatabaseAssistedFolderDriver(): void
    {
        $extractor = DcaExtractor::getInstance('tl_test_with_non_database_assisted_folder_driver');

        $this->assertTrue(isset($GLOBALS['TL_DCA']['tl_test_with_non_database_assisted_folder_driver']));
        $this->assertFalse($extractor->isDbTable());
        $this->assertSame([], $extractor->getKeys());
        $this->assertSame([], $extractor->getFields());
    }

    public function testExtractsFieldsWithEnums(): void
    {
        $extractor = DcaExtractor::getInstance('tl_test_with_enums');

        $this->assertSame(
            [
                'foo' => StringBackedEnum::class,
                'bar' => IntBackedEnum::class,
            ],
            $extractor->getEnums(),
        );
    }

    public function testExtractsVirtualFieldsAndTargets(): void
    {
        $extractor = DcaExtractor::getInstance('tl_test');

        $this->assertSame(['virtualTarget'], $extractor->getVirtualTargets());
        $this->assertSame(['virtualField' => 'virtualTarget'], $extractor->getVirtualFields());
    }

    public function testThrowsInvalidConfigException(): void
    {
        $this->expectException(InvalidConfigException::class);

        DcaExtractor::getInstance('tl_test_with_invalid_config');
    }
}
