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
        $this->locator = new FileLocator($this->mockKernel());
    }

    /**
     * Tests the object instantiation.
     */
    public function testInstantiation()
    {
        $this->assertInstanceOf('Contao\CoreBundle\Config\FileLocator', $this->locator);
    }

    public function testLocateSingleFolder()
    {
        $folders = $this->locator->locate('dca');

        $this->assertCount(1, $folders);
        $this->assertContains($this->getRootDir() . '/vendor/contao/test-bundle/Resources/contao/dca', $folders);
    }

    public function testLocateMultipleFolders()
    {
        $folders = $this->locator->locate('config');

        $this->assertCount(2, $folders);
        $this->assertContains($this->getRootDir() . '/vendor/contao/test-bundle/Resources/contao/config', $folders);
        $this->assertContains($this->getRootDir() . '/system/modules/foobar/config', $folders);
    }

    public function testLocateSingleFile()
    {
        $files = $this->locator->locate('dca/tl_test.php');

        $this->assertCount(1, $files);
        $this->assertContains($this->getRootDir() . '/vendor/contao/test-bundle/Resources/contao/dca/tl_test.php', $files);
    }

    public function testLocateMultipleFiles()
    {
        $files = $this->locator->locate('config/config.php');

        $this->assertCount(2, $files);
        $this->assertContains($this->getRootDir() . '/vendor/contao/test-bundle/Resources/contao/config/config.php', $files);
        $this->assertContains($this->getRootDir() . '/system/modules/foobar/config/config.php', $files);
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testInvalidName()
    {
        $this->locator->locate('');
    }

    public function testFirstFolder()
    {
        $file = $this->locator->locate('config', null, true);

        $this->assertEquals($this->getRootDir() . '/vendor/contao/test-bundle/Resources/contao/config', $file);
    }

    public function testFirstFile()
    {
        $file = $this->locator->locate('config/config.php', null, true);

        $this->assertEquals($this->getRootDir() . '/vendor/contao/test-bundle/Resources/contao/config/config.php', $file);
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testFirstNotFound()
    {
        $file = $this->locator->locate('config/test.php', null, true);
    }

    public function testFirstFileNotFound()
    {
        $file = $this->locator->locate('config/config.php', null, true);

        $this->assertEquals($this->getRootDir() . '/vendor/contao/test-bundle/Resources/contao/config/config.php', $file);
    }

    /**
     * Mocks a kernel.
     *
     * @return Kernel
     */
    private function mockKernel()
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
            ['getName', 'getPath']
        );

        $bundle
            ->expects($this->any())
            ->method('getName')
            ->willReturn('test');

        $bundle
            ->expects($this->any())
            ->method('getPath')
            ->willReturn($this->getRootDir() . '/vendor/contao/test-bundle');

        $module = new ContaoModuleBundle('foobar', $this->getRootDir() . '/app');

        $kernel
            ->expects($this->any())
            ->method('getBundles')
            ->willReturn([$bundle, $module]);

        return $kernel;
    }
}
