<?php

/**
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2015 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Test\HttpKernel;

use Contao\CoreBundle\ContaoCoreBundle;
use Contao\CoreBundle\DependencyInjection\Compiler\AddBundlesToCachePass;
use Contao\CoreBundle\HttpKernel\Bundle\ContaoModuleBundle;
use Contao\CoreBundle\HttpKernel\ContaoKernelInterface;
use Contao\CoreBundle\Test\TestCase;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Tests the ContaoKernel class.
 *
 * @author Leo Feyer <https://github.com/leofeyer>
 */
class ContaoKernelTest extends TestCase
{
    /**
     * @var ContaoKernelInterface
     */
    protected $kernel;

    /**
     * @var \ReflectionClass
     */
    protected $reflection;

    /**
     * Creates a mock object for the abstract ContaoKernel class.
     */
    protected function setUp()
    {
        $this->kernel = $this->getMockForAbstractClass('Contao\CoreBundle\HttpKernel\ContaoKernel', ['test', false]);
        $this->reflection = new \ReflectionClass($this->kernel);

        // Set the root directory
        $rootDir = $this->reflection->getProperty('rootDir');
        $rootDir->setAccessible(true);
        $rootDir->setValue($this->kernel, $this->getRootDir() . '/app');
    }

    /**
     * Tests the object instantiation.
     */
    public function testInstantiation()
    {
        $this->assertInstanceOf('Contao\CoreBundle\HttpKernel\ContaoKernel', $this->kernel);
    }

    /**
     * Tests the addAutoloadBundles() method.
     */
    public function testAutoloadBundles()
    {
        $frameworkBundle = new FrameworkBundle();

        $bundles = [$frameworkBundle];

        $this->kernel->addAutoloadBundles($bundles);

        $this->assertEquals(
            [
                $frameworkBundle,
                new ContaoCoreBundle(),
                new ContaoModuleBundle('legacy-module', $this->getRootDir() . '/app'),
                new ContaoModuleBundle('with-requires', $this->getRootDir() . '/app'),
                new ContaoModuleBundle('without-requires', $this->getRootDir() . '/app'),
            ],
            $bundles
        );

        // Write the bundles cache
        $pass = new AddBundlesToCachePass($this->kernel);
        $pass->process(new ContainerBuilder());
    }

    /**
     * Tests loading the bundle cache.
     */
    public function testLoadBundleCache()
    {
        $this->kernel->loadBundleCache();

        // Make the bundles map accessible
        $bundlesMap = $this->reflection->getProperty('bundlesMap');
        $bundlesMap->setAccessible(true);

        // The FrameworkBundle should not be in the bundles map
        $this->assertEquals(
            [
                'ContaoCoreBundle' => 'Contao\CoreBundle\ContaoCoreBundle',
                'legacy-module'    => null,
                'with-requires'    => null,
                'without-requires' => null,
            ],
            $bundlesMap->getValue($this->kernel)
        );
    }

    /**
     * Tests the getContaoModules() method.
     */
    public function testGetContaoModules()
    {
        // Make the bundles array accessible
        $bundles = $this->reflection->getProperty('bundles');
        $bundles->setAccessible(true);

        $bundles->setValue(
            $this->kernel,
            [
                new FrameworkBundle(),
                new ContaoCoreBundle(),
            ]
        );

        $this->assertEquals(
            [
                new FrameworkBundle(),
                new ContaoCoreBundle(),
            ],
            $this->kernel->getBundles()
        );

        $this->assertEquals(
            [
                new ContaoCoreBundle(),
            ],
            $this->kernel->getContaoBundles()
        );
    }

    /**
     * Tests the buildContainer() method.
     */
    public function testBuildContainer()
    {
        $buildContainer = $this->reflection->getMethod('buildContainer');
        $buildContainer->setAccessible(true);

        $container = $buildContainer->invoke($this->kernel);

        $this->assertInstanceOf('Symfony\Component\DependencyInjection\Container', $container);
    }
}
