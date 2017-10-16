<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2017 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Tests;

use Contao\CoreBundle\ContaoCoreBundle;
use Contao\CoreBundle\DependencyInjection\Compiler\AddImagineClassPass;
use Contao\CoreBundle\DependencyInjection\Compiler\AddPackagesPass;
use Contao\CoreBundle\DependencyInjection\Compiler\AddResourcesPathsPass;
use Contao\CoreBundle\DependencyInjection\Compiler\AddSessionBagsPass;
use Contao\CoreBundle\DependencyInjection\Compiler\DoctrineMigrationsPass;
use Symfony\Component\Console\Application;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class ContaoCoreBundleTest extends TestCase
{
    public function testCanBeInstantiated(): void
    {
        $bundle = new ContaoCoreBundle();

        $this->assertInstanceOf('Contao\CoreBundle\ContaoCoreBundle', $bundle);
    }

    public function testReturnsTheContainerExtension(): void
    {
        $bundle = new ContaoCoreBundle();

        $this->assertInstanceOf(
            'Contao\CoreBundle\DependencyInjection\ContaoCoreExtension',
            $bundle->getContainerExtension()
        );
    }

    public function testDoesNotRegisterAnyCommands(): void
    {
        $application = new Application();
        $commands = $application->all();

        $bundle = new ContaoCoreBundle();
        $bundle->registerCommands($application);

        $this->assertSame($commands, $application->all());
    }

    public function testAddsTheCompilerPaths(): void
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

        $this->assertContains(AddPackagesPass::class, $classes);
        $this->assertContains(AddSessionBagsPass::class, $classes);
        $this->assertContains(AddResourcesPathsPass::class, $classes);
        $this->assertContains(AddImagineClassPass::class, $classes);
        $this->assertContains(DoctrineMigrationsPass::class, $classes);
    }
}
