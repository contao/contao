<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2015 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Test;

use Contao\CoreBundle\ContaoCoreBundle;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Scope;

/**
 * Tests the ContaoCoreBundle class.
 *
 * @author Yanick Witschi <https://github.com/toflar>
 */
class ContaoCoreBundleTest extends TestCase
{
    /**
     * Tests the object instantiation.
     */
    public function testInstantiation()
    {
        $bundle = new ContaoCoreBundle();

        $this->assertInstanceOf('Contao\\CoreBundle\\ContaoCoreBundle', $bundle);
    }

    /**
     * Tests the getContainerExtension() method.
     */
    public function testGetContainerExtension()
    {
        $bundle = new ContaoCoreBundle();

        $this->assertInstanceOf('Contao\\CoreBundle\\DependencyInjection\\ContaoCoreExtension', $bundle->getContainerExtension());
    }

    /**
     * Tests the boot() method.
     */
    public function testBoot()
    {
        $container = new ContainerBuilder();
        $container->addScope(new Scope('request'));

        $bundle = new ContaoCoreBundle();
        $bundle->setContainer($container);
        $bundle->boot();

        $this->assertTrue($container->hasScope(ContaoCoreBundle::SCOPE_BACKEND));
        $this->assertTrue($container->hasScope(ContaoCoreBundle::SCOPE_FRONTEND));
    }

    /**
     * Tests the build() method.
     */
    public function testBuild()
    {
        $container = new ContainerBuilder();
        $container->setParameter('kernel.root_dir', $this->getRootDir());

        $bundle = new ContaoCoreBundle();
        $bundle->build($container);

        $classes = [];

        foreach ($container->getCompilerPassConfig()->getBeforeOptimizationPasses() as $pass) {
            $reflection = new \ReflectionClass($pass);
            $classes[] = $reflection->getName();
        }

        $this->assertEquals(
            [
                'Contao\\CoreBundle\\DependencyInjection\\Compiler\\AddPackagesPass',
                'Contao\\CoreBundle\\DependencyInjection\\Compiler\\AddResourcesPathsPass',
            ],
            $classes
        );
    }
}
