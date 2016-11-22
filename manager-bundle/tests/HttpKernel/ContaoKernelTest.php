<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2016 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\ManagerBundle\Test\HttpKernel;

use Contao\ManagerBundle\ContaoManagerBundle;
use Contao\ManagerBundle\HttpKernel\ContaoKernel;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

/**
 * Tests the ContaoKernel class.
 *
 * @author Leo Feyer <https://github.com/leofeyer>
 */
class ContaoKernelTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ContaoKernel
     */
    private $kernel;

    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        parent::setUp();

        $this->kernel = new ContaoKernel('test', true);
    }

    /**
     * Tests the object instantiation.
     */
    public function testInstantiation()
    {
        $this->assertInstanceOf('Contao\ManagerBundle\HttpKernel\ContaoKernel', $this->kernel);
        $this->assertInstanceOf('Symfony\Component\HttpKernel\Kernel', $this->kernel);
    }

    /**
     * Tests the registerBundles() method.
     */
    public function testRegisterBundles()
    {
        // TODO: call loadPlugins() with a JSON string and add a test plugin

        $this->assertEquals([new ContaoManagerBundle()], $this->kernel->registerBundles());
    }

    /**
     * Tests the getRootDir() method.
     */
    public function testGetRootDir()
    {
        $this->assertEquals(dirname(dirname(dirname(dirname(dirname(__DIR__))))) . '/app', $this->kernel->getRootDir());

        $this->kernel->setRootDir(__DIR__);

        $this->assertEquals(__DIR__, $this->kernel->getRootDir());
    }

    /**
     * Tests the getCacheDir() method.
     */
    public function testGetCacheDir()
    {
        $this->assertEquals(dirname($this->kernel->getRootDir()) . '/var/cache/test', $this->kernel->getCacheDir());
    }

    /**
     * Tests the getLogDir() method.
     */
    public function testGetLogDir()
    {
        $this->assertEquals(dirname($this->kernel->getRootDir()) . '/var/logs', $this->kernel->getLogDir());
    }

    /**
     * Tests the registerContainerConfiguration() method.
     */
    public function testRegisterContainerConfiguration()
    {
        $container = new ContainerBuilder();
        $loader = new YamlFileLoader($container, new FileLocator());

        $this->kernel->registerContainerConfiguration($loader);

        $this->assertEquals('localhost', $container->getParameter('database_host'));
    }
}
