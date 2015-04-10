<?php

/**
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2015 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Test\Command;

use Contao\Connection;
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
     * Creates the ContaoCacheWarmer object.
     */
    protected function setUp()
    {
        $this->warmer = new ContaoCacheWarmer(
            new Filesystem(),
            new ResourceFinder($this->getRootDir() . '/vendor/contao/test-bundle/Resources/contao'),
            new FileLocator($this->getRootDir() . '/vendor/contao/test-bundle/Resources/contao'),
            $this->getRootDir() . '/vendor/contao/test-bundle/Resources/contao',
            new Connection() // FIXME: mock a connection object
        );
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
        /** @var KernelInterface $kernel */
        global $kernel;

        $kernel = $this->mockKernel();

        // The test DCA file needs TL_ROOT to be defined
        if (!defined('TL_ROOT')) {
            define('TL_ROOT', '');
        }

        $this->warmer->warmUp($this->getCacheDir());

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
