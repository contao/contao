<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2015 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Test\Command;

use Contao\CoreBundle\Cache\ContaoCacheWarmer;
use Contao\CoreBundle\Config\ResourceFinder;
use Contao\CoreBundle\Test\TestCase;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpKernel\KernelInterface;

/**
 * Tests the ContaoCacheWarmer class.
 *
 * @author Leo Feyer <https://github.com/leofeyer>
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
    protected function setUp()
    {
        parent::setUp();

        $this->warmer = new ContaoCacheWarmer(
            new Filesystem(),
            new ResourceFinder($this->getRootDir() . '/vendor/contao/test-bundle/Resources/contao'),
            new FileLocator($this->getRootDir() . '/vendor/contao/test-bundle/Resources/contao'),
            $this->getRootDir() . '/vendor/contao/test-bundle/Resources/contao',
            $this->getMock('Doctrine\\DBAL\\Connection', [], [], '', false),
            $this->mockContaoFramework()
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown()
    {
        $fs = new Filesystem();
        $fs->remove($this->getCacheDir() . '/contao');
    }

    /**
     * Tests the object instantiation.
     */
    public function testInstantiation()
    {
        $this->assertInstanceOf('Contao\\CoreBundle\\Cache\\ContaoCacheWarmer', $this->warmer);
    }

    /**
     * Tests the warmUp() method.
     *
     * @runInSeparateProcess
     */
    public function testWarmUp()
    {
        $connection = $this->getMock('Doctrine\\DBAL\\Connection', ['prepare', 'execute', 'fetch'], [], '', false);

        $connection
            ->expects($this->any())
            ->method('prepare')
            ->willReturn($connection)
        ;

        $class1 = new \stdClass();
        $class1->language = 'en-US';

        $class2 = new \stdClass();
        $class2->language = 'en';

        $connection
            ->expects($this->exactly(3))
            ->method('fetch')
            ->willReturnOnConsecutiveCalls($class1, $class2, false)
        ;

        $warmer = new ContaoCacheWarmer(
            new Filesystem(),
            new ResourceFinder($this->getRootDir() . '/vendor/contao/test-bundle/Resources/contao'),
            new FileLocator($this->getRootDir() . '/vendor/contao/test-bundle/Resources/contao'),
            $this->getRootDir() . '/vendor/contao/test-bundle/Resources/contao',
            $connection,
            $this->mockContaoFramework()
        );

        $warmer->warmUp($this->getCacheDir());

        $this->assertFileExists($this->getCacheDir() . '/contao');
        $this->assertFileExists($this->getCacheDir() . '/contao/config');
        $this->assertFileExists($this->getCacheDir() . '/contao/config/autoload.php');
        $this->assertFileExists($this->getCacheDir() . '/contao/config/config.php');
        $this->assertFileExists($this->getCacheDir() . '/contao/config/mapping.php');
        $this->assertFileExists($this->getCacheDir() . '/contao/dca');
        $this->assertFileExists($this->getCacheDir() . '/contao/dca/tl_test.php');
        $this->assertFileExists($this->getCacheDir() . '/contao/languages');
        $this->assertFileExists($this->getCacheDir() . '/contao/languages/en');
        $this->assertFileExists($this->getCacheDir() . '/contao/languages/en/default.php');
        $this->assertFileExists($this->getCacheDir() . '/contao/sql');
        $this->assertFileExists($this->getCacheDir() . '/contao/sql/tl_test.php');

        $this->assertContains("\$GLOBALS['TL_TEST'] = true;", file_get_contents($this->getCacheDir() . '/contao/config/config.php'));
        $this->assertContains('*/empty.fallback', file_get_contents($this->getCacheDir() . '/contao/config/mapping.php'));
        $this->assertContains('test.com/empty.en', file_get_contents($this->getCacheDir() . '/contao/config/mapping.php'));
        $this->assertContains("\$GLOBALS['TL_DCA']['tl_test'] = [\n", file_get_contents($this->getCacheDir() . '/contao/dca/tl_test.php'));
        $this->assertContains("\$GLOBALS['TL_LANG']['MSC']['first']", file_get_contents($this->getCacheDir() . '/contao/languages/en/default.php'));
        $this->assertContains("\$this->arrFields = array (\n  'id' => 'int(10) unsigned NOT NULL auto_increment',\n);", file_get_contents($this->getCacheDir() . '/contao/sql/tl_test.php'));
    }

    /**
     * Tests the isOptional() method.
     */
    public function testIsOptional()
    {
        $this->assertTrue($this->warmer->isOptional());
    }
}
