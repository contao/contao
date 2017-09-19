<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2017 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Tests\Cache;

use Contao\CoreBundle\Cache\ContaoCacheWarmer;
use Contao\CoreBundle\Config\ResourceFinder;
use Contao\CoreBundle\Framework\ContaoFrameworkInterface;
use Contao\CoreBundle\Tests\TestCase;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver\Statement;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Tests the ContaoCacheWarmer class.
 */
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

        $this->warmer = new ContaoCacheWarmer(
            new Filesystem(),
            new ResourceFinder($this->getRootDir().'/vendor/contao/test-bundle/Resources/contao'),
            new FileLocator($this->getRootDir().'/vendor/contao/test-bundle/Resources/contao'),
            $this->getRootDir().'/vendor/contao/test-bundle/Resources/contao',
            $this->createMock(Connection::class),
            $this->mockContaoFramework()
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown(): void
    {
        parent::tearDown();

        $fs = new Filesystem();
        $fs->remove($this->getCacheDir().'/contao');
    }

    /**
     * Tests the object instantiation.
     */
    public function testCanBeInstantiated(): void
    {
        $this->assertInstanceOf('Contao\CoreBundle\Cache\ContaoCacheWarmer', $this->warmer);
    }

    /**
     * Tests creating the cache folder.
     */
    public function testCreatesTheCacheFolder(): void
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

        $warmer = new ContaoCacheWarmer(
            new Filesystem(),
            new ResourceFinder($this->getRootDir().'/vendor/contao/test-bundle/Resources/contao'),
            new FileLocator($this->getRootDir().'/vendor/contao/test-bundle/Resources/contao'),
            $this->getRootDir().'/vendor/contao/test-bundle/Resources/contao',
            $connection,
            $this->mockContaoFramework()
        );

        $warmer->warmUp($this->getCacheDir());

        $this->assertFileExists($this->getCacheDir().'/contao');
        $this->assertFileExists($this->getCacheDir().'/contao/config');
        $this->assertFileExists($this->getCacheDir().'/contao/config/autoload.php');
        $this->assertFileExists($this->getCacheDir().'/contao/config/config.php');
        $this->assertFileExists($this->getCacheDir().'/contao/config/templates.php');
        $this->assertFileExists($this->getCacheDir().'/contao/dca');
        $this->assertFileExists($this->getCacheDir().'/contao/dca/tl_test.php');
        $this->assertFileExists($this->getCacheDir().'/contao/languages');
        $this->assertFileExists($this->getCacheDir().'/contao/languages/en');
        $this->assertFileExists($this->getCacheDir().'/contao/languages/en/default.php');
        $this->assertFileExists($this->getCacheDir().'/contao/sql');
        $this->assertFileExists($this->getCacheDir().'/contao/sql/tl_test.php');

        $this->assertContains(
            "\$GLOBALS['TL_TEST'] = true;",
            file_get_contents($this->getCacheDir().'/contao/config/config.php')
        );

        $this->assertContains(
            "'dummy' => 'templates'",
            file_get_contents($this->getCacheDir().'/contao/config/templates.php')
        );

        $this->assertContains(
            "\$GLOBALS['TL_DCA']['tl_test'] = [\n",
            file_get_contents($this->getCacheDir().'/contao/dca/tl_test.php')
        );

        $this->assertContains(
            "\$GLOBALS['TL_LANG']['MSC']['first']",
            file_get_contents($this->getCacheDir().'/contao/languages/en/default.php')
        );

        $this->assertContains(
            "\$this->arrFields = array (\n  'id' => 'int(10) unsigned NOT NULL auto_increment',\n);",
            file_get_contents($this->getCacheDir().'/contao/sql/tl_test.php')
        );
    }

    /**
     * Tests that the warmer is optional.
     */
    public function testIsAnOptionalWarmer(): void
    {
        $this->assertTrue($this->warmer->isOptional());
    }

    /**
     * Tests that no cache is generated if there are no Contao resources.
     */
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

        $warmer = new ContaoCacheWarmer(
            new Filesystem(),
            new ResourceFinder($this->getRootDir().'/vendor/contao/empty-bundle/Resources/contao'),
            new FileLocator($this->getRootDir().'/vendor/contao/empty-bundle/Resources/contao'),
            $this->getRootDir().'/vendor/contao/empty-bundle/Resources/contao',
            $connection,
            $this->mockContaoFramework()
        );

        $warmer->warmUp($this->getCacheDir());

        $this->assertFileNotExists($this->getCacheDir().'/contao');
    }

    /**
     * Tests that no cache is generated if the installation is incomplete.
     */
    public function testDoesNotCreateTheCacheFolderIfTheInstallationIsIncomplete(): void
    {
        $connection = $this->createMock(Connection::class);

        $connection
            ->method('query')
            ->willThrowException(new \Exception())
        ;

        $framework = $this->createMock(ContaoFrameworkInterface::class);

        $framework
            ->expects($this->never())
            ->method('initialize')
        ;

        $warmer = new ContaoCacheWarmer(
            new Filesystem(),
            new ResourceFinder($this->getRootDir().'/vendor/contao/test-bundle/Resources/contao'),
            new FileLocator($this->getRootDir().'/vendor/contao/test-bundle/Resources/contao'),
            $this->getRootDir().'/vendor/contao/test-bundle/Resources/contao',
            $connection,
            $framework
        );

        $warmer->warmUp($this->getCacheDir());

        $this->assertFileNotExists($this->getCacheDir().'/contao');
    }
}
