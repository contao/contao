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
use Contao\ManagerBundle\ContaoManager\Bundle\Config\ConfigResolverFactory;
use Contao\ManagerBundle\ContaoManager\Bundle\Parser\ParserInterface;
use Contao\ManagerBundle\ContaoManager\PluginLoader;

class BundleLoaderTest extends \PHPUnit_Framework_TestCase
{
    public function testInstanceOf()
    {
        $bundleLoader = new BundleLoader(
            $this->mockPluginLoader(),
            $this->mockConfigResolverFactory(),
            $this->mockParser()
        );

        $this->assertInstanceOf('Contao\ManagerBundle\ContaoManager\Bundle\BundleLoader', $bundleLoader);
    }

    public function testGetBundleConfigsForDevelopment()
    {

    }

    public function testGetBundleConfigsForProduction()
    {

    }

    public function testGetBundleConfigsFromCache()
    {
        $pluginLoader = $this->mockPluginLoader();
        $pluginLoader
            ->expects($this->never())
            ->method('getInstancesOf')
        ;
    }

    private function mockPluginLoader()
    {
        return $this->getMockBuilder(PluginLoader::class)
            ->disableOriginalConstructor()
            ->getMock()
        ;
    }

    private function mockConfigResolverFactory()
    {
        return $this->getMock(ConfigResolverFactory::class);
    }

    private function mockParser()
    {
        return $this->getMock(ParserInterface::class);
    }
}
