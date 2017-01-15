<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2016 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\ManagerBundle\Test\DependencyInjection;

use Contao\ManagerPlugin\Config\ConfigPluginInterface;
use Contao\ManagerPlugin\PluginLoader;
use Contao\ManagerBundle\DependencyInjection\ContaoManagerExtension;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Tests the ContaoManagerExtension class.
 *
 * @author Leo Feyer <https://github.com/leofeyer>
 */
class ContaoManagerExtensionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ContaoManagerExtension
     */
    private $extension;

    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        parent::setUp();

        $this->extension = new ContaoManagerExtension();
    }

    /**
     * Tests the object instantiation.
     */
    public function testInstantiation()
    {
        $this->assertInstanceOf(
            'Contao\ManagerBundle\DependencyInjection\ContaoManagerExtension',
            $this->extension
        );

        $this->assertInstanceOf(
            'Symfony\Component\HttpKernel\DependencyInjection\Extension',
            $this->extension
        );
    }

    /**
     * Tests the prepend() method does nothing if no plugin loader is there.
     */
    public function testPrependDoesNothingWhenNoPluginLoader()
    {
        $container = $this->getMock(ContainerBuilder::class);

        $container
            ->expects($this->atLeastOnce())
            ->method('has')
            ->with('contao_manager.plugin_loader')
            ->willReturn(false)
        ;

        $container
            ->expects($this->never())
            ->method('get')
        ;

        $this->extension->prepend($container);
    }

    /**
     * Tests the prepend() method calls the prependConfig() method of the plugins.
     */
    public function testPrependCallsPluginPrependConfig()
    {
        $container = new ContainerBuilder();

        $pluginLoader = $this->getMockBuilder(PluginLoader::class)
            ->disableOriginalConstructor()
            ->getMock()
        ;

        $pluginLoader
            ->expects($this->atLeastOnce())
            ->method('getInstancesOf')
            ->with(PluginLoader::CONFIG_PLUGINS)
            ->willReturn([$this->mockConfigPlugin($container), $this->mockConfigPlugin($container)])
        ;

        $container->set('contao_manager.plugin_loader', $pluginLoader);
        $container->setParameter('kernel.root_dir', __DIR__.'/../Fixtures/app');

        $this->extension->prepend($container);
    }

    /**
     * Tests the load() method.
     */
    public function testLoad()
    {
        $container = new ContainerBuilder();

        $this->extension->load([], $container);

        $this->assertTrue($container->has('contao_manager.plugin_loader'));
        $this->assertTrue($container->has('contao_manager.routing_loader'));
    }

    private function mockConfigPlugin(ContainerBuilder $container)
    {
        $plugin = $this->getMock(ConfigPluginInterface::class);

        $plugin
            ->expects($this->once())
            ->method('prependConfig')
            ->with([], $container)
        ;

        return $plugin;
    }
}
