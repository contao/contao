<?php

/**
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2015 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Test\Config;

use Contao\CoreBundle\Config\BundleFileLocator;
use Contao\CoreBundle\HttpKernel\Bundle\ContaoModuleBundle;
use Contao\CoreBundle\Test\TestCase;
use Symfony\Component\Config\FileLocatorInterface;
use Symfony\Component\HttpKernel\KernelInterface;

/**
 * Tests the BundleFileLocator class.
 *
 * @author Andreas Schempp <https://github.com/aschempp>
 * @author Leo Feyer <https://github.com/leofeyer>
 */
class BundleFileLocatorTest extends TestCase
{
    /**
     * @var FileLocatorInterface
     */
    private $locator;

    /**
     * Creates the FileLocator object.
     */
    protected function setUp()
    {
        $this->locator = new BundleFileLocator($this->mockKernel());
    }

    /**
     * Tests the object instantiation.
     */
    public function testInstantiation()
    {
        $this->assertInstanceOf('Contao\CoreBundle\Config\BundleFileLocator', $this->locator);
    }

    /**
     * Tests locating a single folder.
     */
    public function testLocateSingleFolder()
    {
        $folder = $this->locator->locate('dca');

        $this->assertInternalType('string', $folder);
        $this->assertEquals($this->getRootDir() . '/vendor/contao/test-bundle/Resources/contao/dca', $folder);
    }

    /**
     * Tests locating multiple folders.
     */
    public function testLocateMultipleFolders()
    {
        $folders = $this->locator->locate('dca', null, false);

        $this->assertInternalType('array', $folders);
        $this->assertCount(1, $folders);
        $this->assertEquals($this->getRootDir() . '/vendor/contao/test-bundle/Resources/contao/dca', $folders[0]);
    }

    /**
     * Tests locating a single file.
     */
    public function testLocateSingleFile()
    {
        $file = $this->locator->locate('dca/tl_test.php');

        $this->assertInternalType('string', $file);
        $this->assertEquals($this->getRootDir() . '/vendor/contao/test-bundle/Resources/contao/dca/tl_test.php', $file);
    }

    /**
     * Tests locating multiple files.
     */
    public function testLocateMultipleFiles()
    {
        $files = $this->locator->locate('config/config.php', null, false);

        $this->assertInternalType('array', $files);
        $this->assertCount(2, $files);
        $this->assertEquals($this->getRootDir() . '/vendor/contao/test-bundle/Resources/contao/config/config.php', $files[0]);
        $this->assertEquals($this->getRootDir() . '/system/modules/foobar/config/config.php', $files[1]);
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
     * Tests locating a non-existing file.
     *
     * @expectedException \InvalidArgumentException
     */
    public function testFirstFileNotFound()
    {
        $this->locator->locate('config/test.php');
    }

    /**
     * Mocks a kernel object.
     *
     * @return \PHPUnit_Framework_MockObject_MockObject|KernelInterface
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

        return $kernel;
    }
}
