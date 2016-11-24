<?php

namespace Contao\ManagerBundle\Test\ContaoManager;

use Contao\ManagerBundle\ContaoManager\PluginLoader;

class PluginLoaderTest extends \PHPUnit_Framework_TestCase
{
    const FIXTURES_DIR = __DIR__ . '/../Fixtures/ContaoManager/PluginLoader';

    public function testInstantiation()
    {
        $pluginLoader = new PluginLoader('foobar');

        $this->assertInstanceOf('Contao\ManagerBundle\ContaoManager\PluginLoader', $pluginLoader);
    }

    /**
     * @runInSeparateProcess
     */
    public function testLoadsPlugin()
    {
        include_once self::FIXTURES_DIR . '/FooBarPlugin.php';

        $pluginLoader = new PluginLoader(self::FIXTURES_DIR . '/installed.json');

        $plugins = $pluginLoader->getInstances();

        $this->assertCount(1, $plugins);
        $this->assertArrayHasKey('foo/bar-bundle', $plugins);
        $this->assertInstanceOf('Foo\Bar\FooBarPlugin', $plugins['foo/bar-bundle']);
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Bar\Foo\BarFooPlugin
     * @expectedExceptionMessage not found
     */
    public function testLoadFailsWhenPluginDoesNotExist()
    {
        $pluginLoader = new PluginLoader(self::FIXTURES_DIR . '/not-installed.json');

        $pluginLoader->getInstances();
    }

    /**
     * @runInSeparateProcess
     */
    public function testGetInstancesOfChecksInterface()
    {
        include_once self::FIXTURES_DIR . '/FooBarPlugin.php';
        include_once self::FIXTURES_DIR . '/FooConfigPlugin.php';

        $pluginLoader = new PluginLoader(self::FIXTURES_DIR . '/mixed.json');

        $plugins = $pluginLoader->getInstancesOf(PluginLoader::CONFIG_PLUGINS);

        $this->assertCount(1, $plugins);
        $this->assertArrayHasKey('foo/config-bundle', $plugins);
        $this->assertArrayNotHasKey('foo/bar-bundle', $plugins);
        $this->assertInstanceOf('Foo\Config\FooConfigPlugin', $plugins['foo/config-bundle']);
    }

    /**
     * @runInSeparateProcess
     */
    public function testLoadsGlobalManagerPlugin()
    {
        include_once self::FIXTURES_DIR . '/ContaoManagerPlugin.php';

        $pluginLoader = new PluginLoader(self::FIXTURES_DIR . '/empty.json');

        $plugins = $pluginLoader->getInstances();

        $this->assertCount(1, $plugins);
        $this->assertArrayHasKey('app', $plugins);
        $this->assertInstanceOf('ContaoManagerPlugin', $plugins['app']);
    }

    /**
     * @runInSeparateProcess
     */
    public function testLoadsManagerBundlePluginFirst()
    {
        include_once self::FIXTURES_DIR . '/FooBarPlugin.php';

        $pluginLoader = new PluginLoader(self::FIXTURES_DIR . '/manager-bundle.json');

        $plugins = $pluginLoader->getInstances();

        $this->assertCount(2, $plugins);
        $this->assertArrayHasKey('foo/bar-bundle', $plugins);
        $this->assertArrayHasKey('contao/manager-bundle', $plugins);
        $this->assertEquals(['contao/manager-bundle', 'foo/bar-bundle'], array_keys($plugins));
    }

    /**
     * @runInSeparateProcess
     */
    public function testLoadOrdersPluginByDependencies()
    {
        include_once self::FIXTURES_DIR . '/FooBarPlugin.php';
        include_once self::FIXTURES_DIR . '/FooDependendPlugin.php';

        $pluginLoader = new PluginLoader(self::FIXTURES_DIR . '/dependencies.json');

        $plugins = $pluginLoader->getInstances();

        $this->assertCount(2, $plugins);
        $this->assertArrayHasKey('foo/bar-bundle', $plugins);
        $this->assertArrayHasKey('foo/dependend-bundle', $plugins);
        $this->assertEquals(['foo/bar-bundle', 'foo/dependend-bundle'], array_keys($plugins));
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage not found
     */
    public function testLoadMissingFile()
    {
        $pluginLoader = new PluginLoader(self::FIXTURES_DIR . '/missing.json');

        $pluginLoader->getInstances();
    }

    /**
     * @expectedException \RuntimeException
     */
    public function testLoadInvalidJson()
    {
        $pluginLoader = new PluginLoader(self::FIXTURES_DIR . '/invalid.json');

        $pluginLoader->getInstances();
    }
}
