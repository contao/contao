<?php

/**
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2015 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Test\Config;

use Contao\CoreBundle\Config\CombinedFileLocator;
use Contao\CoreBundle\Config\FileLocator;
use Contao\CoreBundle\HttpKernel\Bundle\ContaoModuleBundle;
use Contao\CoreBundle\Test\TestCase;
use Symfony\Component\HttpKernel\Kernel;

/**
 * Tests the CombinedFileLocator class.
 *
 * @author Andreas Schempp <https://github.com/aschempp>
 *
 * TODO: add tests for bypassCache=true
 */
class CombinedFileLocatorTest extends TestCase
{
    /**
     * @var CombinedFileLocator
     */
    private $locator;

    /**
     * Creates the FileLocator object.
     */
    protected function setUp()
    {
        $this->locator = new CombinedFileLocator(
            $this->getRootDir() . '/system/cache',
            new FileLocator($this->mockKernel())
        );
    }

    /**
     * Tests the object instantiation.
     */
    public function testInstantiation()
    {
        $this->assertInstanceOf('Contao\CoreBundle\Config\CombinedFileLocator', $this->locator);
    }

    public function testCacheHit()
    {
        $files = $this->locator->locate('config/autoload.php');

        $this->assertCount(1, $files);
        $this->assertContains($this->getRootDir() . '/system/cache/config/autoload.php', $files);
    }

    public function testCacheHitFirst()
    {
        $file = $this->locator->locate('config/autoload.php', null, true);

        $this->assertEquals($this->getRootDir() . '/system/cache/config/autoload.php', $file);
    }

    public function testCacheMiss()
    {
        $files = $this->locator->locate('config/config.php');

        $this->assertCount(2, $files);
        $this->assertContains($this->getRootDir() . '/vendor/contao/test-bundle/Resources/contao/config/config.php', $files);
        $this->assertContains($this->getRootDir() . '/system/modules/foobar/config/config.php', $files);
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testCacheMissFirst()
    {
        $this->locator->locate('config/config.php', null, true);
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
