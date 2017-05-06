<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2017 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Tests\ContaoManager;

use Contao\CoreBundle\ContaoCoreBundle;
use Contao\CoreBundle\ContaoManager\Plugin;
use Contao\ManagerPlugin\Bundle\Config\BundleConfig;
use Contao\ManagerPlugin\Bundle\Parser\DelegatingParser;
use Knp\Bundle\MenuBundle\KnpMenuBundle;
use Knp\Bundle\TimeBundle\KnpTimeBundle;

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
     * Tests the getBundles() method.
     */
    public function testGetBundles()
    {
        $plugin = new Plugin();

        /** @var BundleConfig[] $bundles */
        $bundles = $plugin->getBundles(new DelegatingParser());

        $this->assertCount(3, $bundles);

        $this->assertSame(KnpMenuBundle::class, $bundles[0]->getName());
        $this->assertSame([], $bundles[0]->getReplace());
        $this->assertSame([], $bundles[0]->getLoadAfter());

        $this->assertSame(KnpTimeBundle::class, $bundles[1]->getName());
        $this->assertSame([], $bundles[1]->getReplace());
        $this->assertSame([], $bundles[1]->getLoadAfter());

        $this->assertSame(ContaoCoreBundle::class, $bundles[2]->getName());
        $this->assertSame(['core'], $bundles[2]->getReplace());

        $this->assertSame(
            [
                'Symfony\Bundle\FrameworkBundle\FrameworkBundle',
                'Symfony\Bundle\SecurityBundle\SecurityBundle',
                'Symfony\Bundle\TwigBundle\TwigBundle',
                'Symfony\Bundle\MonologBundle\MonologBundle',
                'Symfony\Bundle\SwiftmailerBundle\SwiftmailerBundle',
                'Doctrine\Bundle\DoctrineBundle\DoctrineBundle',
                'Doctrine\Bundle\DoctrineCacheBundle\DoctrineCacheBundle',
                'Knp\Bundle\TimeBundle\KnpTimeBundle',
                'Lexik\Bundle\MaintenanceBundle\LexikMaintenanceBundle',
                'Sensio\Bundle\FrameworkExtraBundle\SensioFrameworkExtraBundle',
                'Contao\ManagerBundle\ContaoManagerBundle',
            ],
            $bundles[2]->getLoadAfter()
        );
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
