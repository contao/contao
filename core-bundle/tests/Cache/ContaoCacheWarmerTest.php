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
use Doctrine\DBAL\Driver\Statement;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Filesystem\Filesystem;

class ContaoCacheWarmerTest extends TestCase
{
    /**
     * @var ContaoCacheWarmer
     */
    private $warmer;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->warmer = $this->mockCacheWarmer();
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown(): void
    {
        parent::tearDown();

        $fs = new Filesystem();
        $fs->remove($this->getFixturesDir().'/var/cache/contao');
    }

    public function testCreatesTheCacheFolder(): void
    {
        $container = $this->mockContainer($this->getFixturesDir());
        $container->set('database_connection', $this->createMock(Connection::class));

        System::setContainer($container);

        $class1 = new \stdClass();
        $class1->language = 'en-US';

        $class2 = new \stdClass();
        $class2->language = 'en';

        $statement = $this->createMock(Statement::class);
        $statement
            ->expects($this->exactly(3))
            ->method('fetch')
            ->willReturnOnConsecutiveCalls($class1, $class2, false)
        ;

        $connection = $this->createMock(Connection::class);
        $connection
            ->method('prepare')
            ->willReturn($statement)
        ;

        $warmer = $this->mockCacheWarmer($connection);
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

        $this->assertContains(
            "\$GLOBALS['TL_TEST'] = true;",
            file_get_contents($this->getFixturesDir().'/var/cache/contao/config/config.php')
        );

        $this->assertContains(
            "'dummy' => 'templates'",
            file_get_contents($this->getFixturesDir().'/var/cache/contao/config/templates.php')
        );

        $this->assertContains(
            "\$GLOBALS['TL_DCA']['tl_test'] = [\n",
            file_get_contents($this->getFixturesDir().'/var/cache/contao/dca/tl_test.php')
        );

        $this->assertContains(
            "\$GLOBALS['TL_LANG']['MSC']['first']",
            file_get_contents($this->getFixturesDir().'/var/cache/contao/languages/en/default.php')
        );

        $this->assertContains(
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
        $class1 = new \stdClass();
        $class1->language = 'en-US';

        $class2 = new \stdClass();
        $class2->language = 'en';

        $statement = $this->createMock(Statement::class);
        $statement
            ->expects($this->exactly(3))
            ->method('fetch')
            ->willReturnOnConsecutiveCalls($class1, $class2, false)
        ;

        $connection = $this->createMock(Connection::class);
        $connection
            ->method('prepare')
            ->willReturn($statement)
        ;

        $warmer = $this->mockCacheWarmer($connection, null, 'empty-bundle');
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

        $warmer = $this->mockCacheWarmer($connection, $framework);
        $warmer->warmUp($this->getFixturesDir().'/var/cache/contao');

        $this->assertFileNotExists($this->getFixturesDir().'/var/cache/contao');
    }

    private function mockCacheWarmer(Connection $connection = null, ContaoFramework $framework = null, string $bundle = 'test-bundle'): ContaoCacheWarmer
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

        return new ContaoCacheWarmer($filesystem, $finder, $locator, $fixtures, $connection, $framework);
    }
}
