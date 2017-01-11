<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2016 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Test;

use Contao\CoreBundle\ContaoCoreBundle;
use Symfony\Component\DependencyInjection\ContainerBuilder;

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

        $this->assertInstanceOf('Contao\CoreBundle\ContaoCoreBundle', $bundle);
    }

    /**
     * Tests the getContainerExtension() method.
     */
    public function testGetContainerExtension()
    {
        $bundle = new ContaoCoreBundle();

        $this->assertInstanceOf(
            'Contao\CoreBundle\DependencyInjection\ContaoCoreExtension',
            $bundle->getContainerExtension()
        );
    }

    /**
     * Tests the build() method.
     */
    public function testBuild()
    {
        $container = new ContainerBuilder();
        $container->setParameter('kernel.root_dir', $this->getRootDir().'/app');

        $bundle = new ContaoCoreBundle();
        $bundle->build($container);

        $classes = [];

        foreach ($container->getCompilerPassConfig()->getPasses() as $pass) {
            $reflection = new \ReflectionClass($pass);
            $classes[] = $reflection->getName();
        }

        $this->assertContains('Contao\CoreBundle\DependencyInjection\Compiler\AddPackagesPass', $classes);
        $this->assertContains('Contao\CoreBundle\DependencyInjection\Compiler\AddSessionBagsPass', $classes);
        $this->assertContains('Contao\CoreBundle\DependencyInjection\Compiler\AddResourcesPathsPass', $classes);
        $this->assertContains('Contao\CoreBundle\DependencyInjection\Compiler\AddImagineClassPass', $classes);
        $this->assertContains('Contao\CoreBundle\DependencyInjection\Compiler\DoctrineMigrationsPass', $classes);
    }
}
