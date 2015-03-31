<?php

/**
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2015 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Test\Config;

use Contao\CoreBundle\Config\FileLocator;
use Contao\CoreBundle\HttpKernel\Bundle\ContaoModuleBundle;
use Contao\CoreBundle\Test\TestCase;
use Symfony\Component\HttpKernel\Kernel;

/**
 * Tests the FileLocator class.
 *
 * @author Andreas Schempp <https://github.com/aschempp>
 *
 * TODO: add tests for currentDir parameter
 */
class FileLocatorTest extends TestCase
{
    /**
     * @var FileLocator
     */
    private $locator;

    /**
     * Creates the FileLocator object.
     */
    protected function setUp()
    {
        $this->locator = new FileLocator([
            'TestBundle' => $this->getRootDir() . '/vendor/contao/test-bundle/Resources/contao',
            'foobar'     => $this->getRootDir() . '/system/modules/foobar'
        ]);
    }

    /**
     * Tests the object instantiation.
     */
    public function testInstantiation()
    {
        $this->assertInstanceOf('Contao\CoreBundle\Config\FileLocator', $this->locator);
    }

    /**
     * Tests locating a single folder.
     */
    public function testLocateSingleFolder()
    {
        $folders = $this->locator->locate('dca');

        $this->assertCount(1, $folders);
        $this->assertContains($this->getRootDir() . '/vendor/contao/test-bundle/Resources/contao/dca', $folders);
    }

    /**
     * Tests locating multiple folders.
     */
    public function testLocateMultipleFolders()
    {
        $folders = $this->locator->locate('config');

        $this->assertCount(2, $folders);
        $this->assertContains($this->getRootDir() . '/vendor/contao/test-bundle/Resources/contao/config', $folders);
        $this->assertContains($this->getRootDir() . '/system/modules/foobar/config', $folders);
    }

    /**
     * Tests locating a single file.
     */
    public function testLocateSingleFile()
    {
        $files = $this->locator->locate('dca/tl_test.php');

        $this->assertCount(1, $files);
        $this->assertContains($this->getRootDir() . '/vendor/contao/test-bundle/Resources/contao/dca/tl_test.php', $files);
    }

    /**
     * Tests locating multiple files.
     */
    public function testLocateMultipleFiles()
    {
        $files = $this->locator->locate('config/config.php');

        $this->assertCount(2, $files);
        $this->assertContains($this->getRootDir() . '/vendor/contao/test-bundle/Resources/contao/config/config.php', $files);
        $this->assertContains($this->getRootDir() . '/system/modules/foobar/config/config.php', $files);
    }

    /**
     * Tests locating an invalid resource.
     *
     * @expectedException \InvalidArgumentException
     */
    public function testInvalidName()
    {
        $this->locator->locate('');
    }

    /**
     * Tests locating the first folder.
     */
    public function testFirstFolder()
    {
        $file = $this->locator->locate('config', null, true);

        $this->assertEquals($this->getRootDir() . '/vendor/contao/test-bundle/Resources/contao/config', $file);
    }

    /**
     * Tests locating the first file.
     */
    public function testFirstFile()
    {
        $file = $this->locator->locate('config/config.php', null, true);

        $this->assertEquals($this->getRootDir() . '/vendor/contao/test-bundle/Resources/contao/config/config.php', $file);
    }

    /**
     * Tests locating a non-existing file.
     *
     * @expectedException \InvalidArgumentException
     */
    public function testFirstFileNotFound()
    {
        $this->locator->locate('config/test.php', null, true);
    }


    public function testBundleNames()
    {
        $bundles = array_keys($this->locator->locate('config'));

        $this->assertContains('TestBundle', $bundles);
        $this->assertContains('foobar', $bundles);
    }


    public function testFactory()
    {
        $kernel = $this->getMock(
            'Symfony\Component\HttpKernel\Kernel',
            [
                // KernelInterface
                'registerBundles',
                'registerContainerConfiguration',
                'boot',
                'shutdown',
                'getBundles',
                'isClassInActiveBundle',
                'getBundle',
                'locateResource',
                'getName',
                'getEnvironment',
                'isDebug',
                'getRootDir',
                'getContainer',
                'getStartTime',
                'getCacheDir',
                'getLogDir',
                'getCharset',

                // HttpKernelInterface
                'handle',

                // Serializable
                'serialize',
                'unserialize',
            ],
            ['test', false]
        );

        $bundle = $this->getMock(
            'Symfony\Component\HttpKernel\Bundle\Bundle',
            ['getName', 'getPath'],
            [],
            'TestBundle'
        );

        $bundle
            ->expects($this->any())
            ->method('getPath')
            ->willReturn($this->getRootDir() . '/vendor/contao/test-bundle')
        ;

        $module = new ContaoModuleBundle('foobar', $this->getRootDir() . '/app');

        $kernel
            ->expects($this->any())
            ->method('getBundles')
            ->willReturn([$bundle, $module])
        ;

        $locator = FileLocator::createFromKernelBundles($kernel);
        $files   = $locator->locate('config/config.php');

        $this->assertCount(2, $files);
        $this->assertContains($this->getRootDir() . '/vendor/contao/test-bundle/Resources/contao/config/config.php', $files);
        $this->assertContains($this->getRootDir() . '/system/modules/foobar/config/config.php', $files);
    }
}
