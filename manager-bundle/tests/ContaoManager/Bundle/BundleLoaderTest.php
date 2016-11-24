<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2016 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\ManagerBundle\Test\ContaoManager\Bundle;

use Contao\ManagerBundle\ContaoManager\Bundle\BundleLoader;
use Contao\ManagerBundle\ContaoManager\Bundle\BundlePluginInterface;
use Contao\ManagerBundle\ContaoManager\Bundle\Config\BundleConfig;
use Contao\ManagerBundle\ContaoManager\Bundle\Config\ConfigResolverFactory;
use Contao\ManagerBundle\ContaoManager\Bundle\Config\ConfigResolverInterface;
use Contao\ManagerBundle\ContaoManager\Bundle\Parser\ParserInterface;
use Contao\ManagerBundle\ContaoManager\PluginLoader;
use Symfony\Component\Filesystem\Filesystem;

class BundleLoaderTest extends \PHPUnit_Framework_TestCase
{
    public function testInstantiation()
    {
        $bundleLoader = new BundleLoader(
            $this->mockPluginLoader($this->never()),
            $this->mockConfigResolverFactory(0, false),
            $this->mockParser()
        );

        $this->assertInstanceOf('Contao\ManagerBundle\ContaoManager\Bundle\BundleLoader', $bundleLoader);
    }

    /**
     * @dataProvider bundleConfigsProvider
     */
    public function testGetBundleConfigs($plugins, $configCount, $development)
    {
        $bundleLoader = new BundleLoader(
            $this->mockPluginLoader($this->atLeastOnce(), $plugins),
            $this->mockConfigResolverFactory($configCount, $development),
            $this->mockParser()
        );

        $bundleLoader->getBundleConfigs($development);
    }

    public function testGetBundleConfigsReadsFromCacheFile()
    {
        $cacheFile = tempnam(sys_get_temp_dir(), 'BundleLoader_');

        file_put_contents($cacheFile, serialize([new BundleConfig('foobar')]));

        $bundleLoader = new BundleLoader(
            $this->mockPluginLoader($this->never()),
            $this->mockConfigResolverFactory(0, false),
            $this->mockParser()
        );

        $configs = $bundleLoader->getBundleConfigs(false, $cacheFile);

        $this->assertCount(1, $configs);
        $this->assertInstanceOf(BundleConfig::class, $configs[0]);
    }

    public function testGetBundleConfigsIgnoresEmptyCacheFile()
    {
        // Empty file does not contain valid cache
        $cacheFile = tempnam(sys_get_temp_dir(), 'BundleLoader_');

        $bundleLoader = new BundleLoader(
            $this->mockPluginLoader($this->atLeastOnce(), [$this->mockBundlePlugin([new BundleConfig('foobar')])]),
            $this->mockConfigResolverFactory(1, false),
            $this->mockParser(),
            $this->getMock(Filesystem::class)
        );

        $bundleLoader->getBundleConfigs(false, $cacheFile);
    }

    public function testGetBundleConfigsDumpsToCacheFile()
    {
        $cacheFile = sys_get_temp_dir() . '/' . uniqid('BundleLoader_', false);
        $configs = [new BundleConfig('foobar')];

        $this->assertFalse(file_exists($cacheFile));

        $filesystem = $this->getMock(Filesystem::class);
        $filesystem
            ->expects($this->once())
            ->method('dumpFile')
            ->with($cacheFile)
        ;

        $bundleLoader = new BundleLoader(
            $this->mockPluginLoader($this->atLeastOnce(), [$this->mockBundlePlugin($configs)]),
            $this->mockConfigResolverFactory(1, false),
            $this->mockParser(),
            $filesystem
        );

        $bundleLoader->getBundleConfigs(false, $cacheFile);

        $this->assertFalse(file_exists($cacheFile));
    }

    public function bundleConfigsProvider()
    {
        return [
            'Test correctly calls development mode' => [
                [$this->mockBundlePlugin([new BundleConfig('foobar')])],
                1,
                true
            ],
            'Test correctly calls production mode' => [
                [$this->mockBundlePlugin([new BundleConfig('foobar')])],
                1,
                false
            ],
            'Test correctly adds multiple configs from a plugin' => [
                [$this->mockBundlePlugin([new BundleConfig('foo'), new BundleConfig('bar')])],
                2,
                true
            ],
            'Test correctly adds config from multiple plugins' => [
                [
                    $this->mockBundlePlugin([new BundleConfig('foo')]),
                    $this->mockBundlePlugin([new BundleConfig('bar')])
                ],
                2,
                false
            ],
            'Test ignores plugin without bundles' => [
                [
                    $this->mockBundlePlugin([new BundleConfig('foo')]),
                    $this->mockBundlePlugin([new BundleConfig('bar')]),
                    $this->mockBundlePlugin(),
                ],
                2,
                false
            ],
        ];
    }

    private function mockBundlePlugin($configs = [])
    {
        $mock = $this->getMock(BundlePluginInterface::class);

        $mock
            ->method('getBundles')
            ->willReturn($configs)
        ;

        return $mock;
    }

    private function mockPluginLoader(
        \PHPUnit_Framework_MockObject_Matcher_InvokedRecorder $expects,
        array $plugins = []
    ) {
        $mock = $this->getMockBuilder(PluginLoader::class)
            ->disableOriginalConstructor()
            ->getMock()
        ;

        $mock
            ->expects($expects)
            ->method('getInstancesOf')
            ->with(PluginLoader::BUNDLE_PLUGINS)
            ->willReturn($plugins)
        ;

        return $mock;
    }

    private function mockConfigResolverFactory($addCount, $development)
    {
        $factory = $this->getMock(ConfigResolverFactory::class);
        $resolver = $this->getMock(ConfigResolverInterface::class);

        $factory
            ->method('create')
            ->willReturn($resolver)
        ;

        $resolver
            ->expects($this->exactly($addCount))
            ->method('add')
        ;

        $resolver
            ->expects($this->any())
            ->method('getBundleConfigs')
            ->with($development)
            ->willReturn([])
        ;

        return $factory;
    }

    private function mockParser()
    {
        return $this->getMock(ParserInterface::class);
    }
}
