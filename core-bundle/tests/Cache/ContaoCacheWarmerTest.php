<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Cache;

use Contao\Config;
use Contao\CoreBundle\Cache\ContaoCacheWarmer;
use Contao\CoreBundle\Config\ResourceFinder;
use Contao\CoreBundle\Doctrine\Schema\SchemaProvider;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Intl\Locales;
use Contao\CoreBundle\Tests\TestCase;
use Contao\DcaExtractor;
use Contao\DcaLoader;
use Contao\System;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\Schema;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;

class ContaoCacheWarmerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        (new Filesystem())->mkdir([
            Path::join(self::getTempDir(), 'var/cache'),
            Path::join(self::getTempDir(), 'other'),
        ]);

        $schemaProvider = $this->createMock(SchemaProvider::class);
        $schemaProvider
            ->method('createSchema')
            ->willReturn(new Schema())
        ;

        $container = $this->getContainerWithContaoConfiguration($this->getTempDir());
        $container->set('contao.doctrine.schema_provider', $schemaProvider);
        $container->set('database_connection', $this->createMock(Connection::class));

        System::setContainer($container);
    }

    protected function tearDown(): void
    {
        (new Filesystem())->remove(Path::join($this->getTempDir(), 'var/cache/contao'));

        unset($GLOBALS['TL_TEST'], $GLOBALS['TL_DCA'], $GLOBALS['TL_LANG'], $GLOBALS['TL_MIME']);

        $this->resetStaticProperties([DcaExtractor::class, DcaLoader::class, System::class, Config::class]);

        parent::tearDown();
    }

    public function testCreatesTheCacheFolder(): void
    {
        $warmer = $this->getCacheWarmer();
        $warmer->warmUp(Path::join($this->getTempDir(), 'var/cache'));

        $this->assertFileExists(Path::join($this->getTempDir(), 'var/cache/contao'));
        $this->assertFileExists(Path::join($this->getTempDir(), 'var/cache/contao/config'));
        $this->assertFileExists(Path::join($this->getTempDir(), 'var/cache/contao/config/autoload.php'));
        $this->assertFileExists(Path::join($this->getTempDir(), 'var/cache/contao/config/config.php'));
        $this->assertFileExists(Path::join($this->getTempDir(), 'var/cache/contao/config/templates.php'));
        $this->assertFileExists(Path::join($this->getTempDir(), 'var/cache/contao/config/available-language-files.php'));
        $this->assertFileExists(Path::join($this->getTempDir(), 'var/cache/contao/dca'));
        $this->assertFileExists(Path::join($this->getTempDir(), 'var/cache/contao/dca/tl_test.php'));
        $this->assertFileExists(Path::join($this->getTempDir(), 'var/cache/contao/languages'));
        $this->assertFileExists(Path::join($this->getTempDir(), 'var/cache/contao/languages/en'));
        $this->assertFileExists(Path::join($this->getTempDir(), 'var/cache/contao/languages/en/default.php'));
        $this->assertFileExists(Path::join($this->getTempDir(), 'var/cache/contao/sql'));
        $this->assertFileExists(Path::join($this->getTempDir(), 'var/cache/contao/sql/tl_test.php'));

        $this->assertStringContainsString(
            "\$GLOBALS['TL_TEST'] = \\true;",
            file_get_contents(Path::join($this->getTempDir(), 'var/cache/contao/config/config.php'))
        );

        $this->assertStringContainsString(
            "'dummy' => 'templates'",
            file_get_contents(Path::join($this->getTempDir(), 'var/cache/contao/config/templates.php'))
        );

        $this->assertStringContainsString(
            "\$GLOBALS['TL_DCA']['tl_test'] = [",
            file_get_contents(Path::join($this->getTempDir(), 'var/cache/contao/dca/tl_test.php'))
        );

        $this->assertStringContainsString(
            "\$GLOBALS['TL_LANG']['MSC']['first']",
            file_get_contents(Path::join($this->getTempDir(), 'var/cache/contao/languages/en/default.php'))
        );

        $this->assertStringContainsString(
            "\$this->arrFields = array (\n  'id' => 'int(10) unsigned NOT NULL auto_increment',\n);",
            file_get_contents(Path::join($this->getTempDir(), 'var/cache/contao/sql/tl_test.php'))
        );

        $expected = <<<'TXT'
            <?php
            return array (
              'en' =>
              array (
                'default' => true,
                'tl_test' => true,
              ),
            );

            TXT;

        $file = Path::join($this->getTempDir(), 'var/cache/contao/config/available-language-files.php');

        $this->assertSame($expected, preg_replace('/\s+\n/', "\n", file_get_contents($file)));
    }

    public function testIsAnOptionalWarmer(): void
    {
        $this->assertTrue($this->getCacheWarmer()->isOptional());
    }

    public function testDoesNotCreateTheCacheFolderIfThereAreNoContaoResources(): void
    {
        $warmer = $this->getCacheWarmer(null, null, 'empty-bundle');
        $warmer->warmUp(Path::join($this->getTempDir(), 'other'));

        $this->assertFileDoesNotExist(Path::join($this->getTempDir(), 'var/cache/contao'));
    }

    public function testDoesNotCreateTheCacheFolderIfTheInstallationIsIncomplete(): void
    {
        $connection = $this->createMock(Connection::class);
        $connection
            ->method('executeQuery')
            ->willThrowException(new \Exception())
        ;

        $framework = $this->mockContaoFramework();
        $framework
            ->expects($this->never())
            ->method('initialize')
        ;

        $warmer = $this->getCacheWarmer($connection, $framework);
        $warmer->warmUp(Path::join($this->getTempDir(), 'var/cache/contao'));

        $this->assertFileDoesNotExist(Path::join($this->getTempDir(), 'var/cache/contao'));
    }

    private function getCacheWarmer(Connection|null $connection = null, ContaoFramework|null $framework = null, string $bundle = 'test-bundle'): ContaoCacheWarmer
    {
        $connection ??= $this->createMock(Connection::class);
        $framework ??= $this->mockContaoFramework();

        $fixtures = Path::join($this->getFixturesDir(), 'vendor/contao/'.$bundle.'/Resources/contao');

        $filesystem = new Filesystem();
        $finder = new ResourceFinder($fixtures);
        $locator = new FileLocator($fixtures);

        $locales = $this->createMock(Locales::class);
        $locales
            ->method('getEnabledLocaleIds')
            ->willReturn(['en-US', 'en'])
        ;

        return new ContaoCacheWarmer($filesystem, $finder, $locator, $fixtures, $connection, $framework, $locales);
    }
}
