<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2016 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Test\ContaoManager;

use Contao\CoreBundle\ContaoManager\Plugin;

/**
 * Tests the Plugin class.
 *
 * @author Leo Feyer <https://github.com/leofeyer>
 */
class PluginTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Tests the object instantiation.
     */
    public function testInstantiation()
    {
        $plugin = new Plugin();

        $this->assertInstanceOf('Contao\CoreBundle\ContaoManager\Plugin', $plugin);
    }

    /**
     * Tests the getRouteCollection() method.
     */
    public function testGetRouteCollection()
    {
        $loader = $this
            ->getMockBuilder('Symfony\Component\Config\Loader\LoaderInterface')
            ->setMethods(['load', 'supports', 'getResolver', 'setResolver'])
            ->getMock()
        ;

        $loader
            ->expects($this->once())
            ->method('load')
        ;

        $resolver = $this
            ->getMockBuilder('Symfony\Component\Config\Loader\LoaderResolverInterface')
            ->setMethods(['resolve'])
            ->getMock()
        ;

        $resolver
            ->expects($this->any())
            ->method('resolve')
            ->willReturn($loader)
        ;

        $plugin = new Plugin();
        $plugin->getRouteCollection($resolver, $this->getMock('Symfony\Component\HttpKernel\KernelInterface'));
    }
}
