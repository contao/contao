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

use Contao\CoreBundle\Cache\ContaoCacheWarmer;
use Contao\CoreBundle\Config\ResourceFinder;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Tests\TestCase;
use Contao\System;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Config\Exception\FileLocatorFileNotFoundException;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Filesystem\Filesystem;

class ContaoCacheWarmerTest extends TestCase
{
    /**
     * @var ContaoCacheWarmer
     */
    private $warmer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->warmer = $this->getCacheWarmer();
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $fs = new Filesystem();
        $fs->remove($this->getFixturesDir().'/var/cache/contao');
    }

    public function testCreatesTheCacheFolder(): void
    {
        $resourceLocator = $this->createMock(FileLocator::class);
        $resourceLocator
            ->method('locate')
            ->with('config/database.sql', null, false)
            ->willThrowException(new FileLocatorFileNotFoundException())
        ;

        $container = $this->getContainerWithContaoConfiguration($this->getFixturesDir());
        $container->set('database_connection', $this->createMock(Connection::class));
        $container->set('contao.resource_locator', $resourceLocator);

        System::setContainer($container);

        $warmer = $this->getCacheWarmer();
        $warmer->warmUp($this->getFixturesDir().'/var/cache');

        $this->assertFileExists($this->getFixturesDir().'/var/cache/contao');
        $this->assertFileExists($this->getFixturesDir().'/var/cache/contao/config');
        $this->assertFileExists($this->getFixturesDir().'/var/cache/contao/config/autoload.php');
        $this->assertFileExists($this->getFixturesDir().'/var/cache/contao/config/config.php');
        $this->assertFileExists($this->getFixturesDir().'/var/cache/contao/config/templates.php');
        $this->assertFileExists($this->getFixturesDir().'/var/cache/contao/dca');
        $this->assertFileExists($this->getFixturesDir().'/var/cache/contao/dca/tl_test.php');
        $this->assertFileExists($this->getFixturesDir().'/var/cache/contao/languages');
        $this->assertFileExists($this->getFixturesDir().'/var/cache/contao/languages/en');
        $this->assertFileExists($this->getFixturesDir().'/var/cache/contao/languages/en/default.php');
        $this->assertFileExists($this->getFixturesDir().'/var/cache/contao/sql');
        $this->assertFileExists($this->getFixturesDir().'/var/cache/contao/sql/tl_test.php');

        $this->assertStringContainsString(
            "\$GLOBALS['TL_TEST'] = true;",
            file_get_contents($this->getFixturesDir().'/var/cache/contao/config/config.php')
        );

        $this->assertStringContainsString(
            "'dummy' => 'templates'",
            file_get_contents($this->getFixturesDir().'/var/cache/contao/config/templates.php')
        );

        $this->assertStringContainsString(
            "\$GLOBALS['TL_DCA']['tl_test'] = [\n",
            file_get_contents($this->getFixturesDir().'/var/cache/contao/dca/tl_test.php')
        );

        $this->assertStringContainsString(
            "\$GLOBALS['TL_LANG']['MSC']['first']",
            file_get_contents($this->getFixturesDir().'/var/cache/contao/languages/en/default.php')
        );

        $this->assertStringContainsString(
            "\$this->arrFields = array (\n  'id' => 'int(10) unsigned NOT NULL auto_increment',\n);",
            file_get_contents($this->getFixturesDir().'/var/cache/contao/sql/tl_test.php')
        );
    }

    public function testIsAnOptionalWarmer(): void
    {
        $this->assertTrue($this->warmer->isOptional());
    }

    public function testDoesNotCreateTheCacheFolderIfThereAreNoContaoResources(): void
    {
        $warmer = $this->getCacheWarmer(null, null, 'empty-bundle');
        $warmer->warmUp($this->getFixturesDir().'/var/cache/contao');

        $this->assertFileNotExists($this->getFixturesDir().'/var/cache/contao');
    }

    public function testDoesNotCreateTheCacheFolderIfTheInstallationIsIncomplete(): void
    {
        $connection = $this->createMock(Connection::class);
        $connection
            ->method('query')
            ->willThrowException(new \Exception())
        ;

        $framework = $this->mockContaoFramework();
        $framework
            ->expects($this->never())
            ->method('initialize')
        ;

        $warmer = $this->getCacheWarmer($connection, $framework);
        $warmer->warmUp($this->getFixturesDir().'/var/cache/contao');

        $this->assertFileNotExists($this->getFixturesDir().'/var/cache/contao');
    }

    /**
     * @param Connection&MockObject      $connection
     * @param ContaoFramework&MockObject $framework
     */
    private function getCacheWarmer(Connection $connection = null, ContaoFramework $framework = null, string $bundle = 'test-bundle'): ContaoCacheWarmer
    {
        if (null === $connection) {
            $connection = $this->createMock(Connection::class);
        }

        if (null === $framework) {
            $framework = $this->mockContaoFramework();
        }

        $fixtures = $this->getFixturesDir().'/vendor/contao/'.$bundle.'/Resources/contao';

        $filesystem = new Filesystem();
        $finder = new ResourceFinder($fixtures);
        $locator = new FileLocator($fixtures);
        $locales = ['en-US', 'en'];

        return new ContaoCacheWarmer($filesystem, $finder, $locator, $fixtures, $connection, $framework, $locales);
    }
}
