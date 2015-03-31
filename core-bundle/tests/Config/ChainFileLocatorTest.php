<?php

/**
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2015 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Test\Config;

use Contao\CoreBundle\Config\ChainFileLocator;
use Contao\CoreBundle\Config\CombinedFileLocator;
use Contao\CoreBundle\Config\FileLocator;
use Contao\CoreBundle\HttpKernel\Bundle\ContaoModuleBundle;
use Contao\CoreBundle\Test\TestCase;
use Symfony\Component\HttpKernel\Kernel;

/**
 * Tests the ChainFileLocator class.
 *
 * @author Andreas Schempp <https://github.com/aschempp>
 */
class ChainFileLocatorTest extends TestCase
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
        $this->locator = new ChainFileLocator();
        $this->locator->addLocator(new CombinedFileLocator($this->getCacheDir() . '/contao'));
        $this->locator->addLocator(new FileLocator($this->mockKernel()));
    }

    /**
     * Tests the object instantiation.
     */
    public function testInstantiation()
    {
        $this->assertInstanceOf('Contao\CoreBundle\Config\ChainFileLocator', $this->locator);
    }

    /**
     * Tests locating all resources.
     */
    public function testLocateAll()
    {
        $files = array_values($this->locator->locate('config/autoload.php'));

        $this->assertCount(3, $files);
        // FIXME: had to change the order because ChainFileLocator::getLocators() did not correctly reverse the
        // order of the locators. Why do we have to reverse the order at all?
        $this->assertEquals($this->getRootDir() . '/vendor/contao/test-bundle/Resources/contao/config/autoload.php', $files[0]);
        $this->assertEquals($this->getRootDir() . '/system/modules/foobar/config/autoload.php', $files[1]);
        $this->assertEquals($this->getCacheDir() . '/contao/config/autoload.php', $files[2]);
    }

    /**
     * Tests locating the first resource.
     */
    public function testLocateFirst()
    {
        $file = $this->locator->locate('config/autoload.php', null, true);

        $this->assertEquals($this->getCacheDir() . '/contao/config/autoload.php', $file);
    }

    /**
     * Tests locating the first resource with a non-existing file.
     *
     * @expectedException \InvalidArgumentException
     */
    public function testLocateFirstNotFound()
    {
        $this->locator->locate('config/foo.php', null, true);
    }

    /**
     * Returns a mocked kernel object.
     *
     * @return Kernel The kernel object.
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
            ->willReturn('test')
        ;

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
