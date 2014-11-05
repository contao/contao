<?php

/**
 * Contao Open Source CMS
 *
 * Copyright (c) 2005-2014 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Test\HttpKernel;

use Contao\CoreBundle\ContaoCoreBundle;
use Contao\CoreBundle\DependencyInjection\Compiler\AddBundlesToCachePass;
use Contao\CoreBundle\HttpKernel\Bundle\ContaoModuleBundle;
use Contao\CoreBundle\HttpKernel\ContaoKernelInterface;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Tests the ContaoKernel class.
 *
 * @author Leo Feyer <https://contao.org>
 */
class ContaoKernelTest extends \PHPUnit_Framework_TestCase
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
     * Creates the cache directory.
     */
    public static function setUpBeforeClass()
    {
        mkdir(__DIR__ . '/../Fixtures/HttpKernel/vendor/cache/test', 0755, true);
    }

    /**
     * Removes the cache directory.
     */
    public static function tearDownAfterClass()
    {
        unlink(__DIR__ . '/../Fixtures/HttpKernel/vendor/cache/test/bundles.map');
        rmdir(__DIR__ . '/../Fixtures/HttpKernel/vendor/cache/test');
        rmdir(__DIR__ . '/../Fixtures/HttpKernel/vendor/cache');
    }

    /**
     * Creates a mock object for the abstract ContaoKernel class.
     */
    protected function setUp()
    {
        $this->kernel = $this->getMockForAbstractClass('Contao\CoreBundle\HttpKernel\ContaoKernel');
        $this->reflection = new \ReflectionClass($this->kernel);

        // Set the root directory
        $rootDir = $this->reflection->getProperty('rootDir');
        $rootDir->setAccessible(true);
        $rootDir->setValue($this->kernel, __DIR__ . '/../Fixtures/HttpKernel/vendor');

        // Set the environment
        $environment = $this->reflection->getProperty('environment');
        $environment->setAccessible(true);
        $environment->setValue($this->kernel, 'test');
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

        $bundles = [
            $frameworkBundle
        ];

        $this->kernel->addAutoloadBundles($bundles);

        $this->assertEquals(
            [
                $frameworkBundle,
                new ContaoCoreBundle(),
                new ContaoModuleBundle('legacy-module', __DIR__ . '/../Fixtures/HttpKernel/vendor')
            ],
            $bundles
        );

        // Write the bundles cache
        $pass = new AddBundlesToCachePass($this->kernel);
        $pass->process(new ContainerBuilder());

        $this->assertFileExists(__DIR__ . '/../Fixtures/HttpKernel/vendor/cache/test');
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
                'legacy-module'    => null
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
                new ContaoCoreBundle()
            ]
        );

        $this->assertEquals(
            [
                new FrameworkBundle(),
                new ContaoCoreBundle()
            ],
            $this->kernel->getBundles()
        );

        $this->assertEquals(
            [
                new ContaoCoreBundle()
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
