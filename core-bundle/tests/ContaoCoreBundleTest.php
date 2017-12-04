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
use Contao\CoreBundle\DependencyInjection\Compiler\AddAssetsPackagesPass;
use Contao\CoreBundle\DependencyInjection\Compiler\AddImagineClassPass;
use Contao\CoreBundle\DependencyInjection\Compiler\AddPackagesPass;
use Contao\CoreBundle\DependencyInjection\Compiler\AddResourcesPathsPass;
use Contao\CoreBundle\DependencyInjection\Compiler\AddSessionBagsPass;
use Contao\CoreBundle\DependencyInjection\Compiler\DoctrineMigrationsPass;
use Contao\CoreBundle\DependencyInjection\Compiler\MapFragmentsToGlobalsPass;
use Contao\CoreBundle\DependencyInjection\Compiler\PickerProviderPass;
use Contao\CoreBundle\DependencyInjection\Compiler\RegisterFragmentsPass;
use Contao\CoreBundle\DependencyInjection\Compiler\RegisterHookListenersPass;
use Symfony\Component\Console\Application;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\DependencyInjection\FragmentRendererPass;

class ContaoCoreBundleTest extends TestCase
{
    public function testCanBeInstantiated(): void
    {
        $bundle = new ContaoCoreBundle();

        $this->assertInstanceOf('Contao\CoreBundle\ContaoCoreBundle', $bundle);
    }

    public function testReturnsTheContainerExtension(): void
    {
        $extension = (new ContaoCoreBundle())->getContainerExtension();

        $this->assertInstanceOf('Contao\CoreBundle\DependencyInjection\ContaoCoreExtension', $extension);
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
        $passes = [
            AddPackagesPass::class,
            AddAssetsPackagesPass::class,
            AddSessionBagsPass::class,
            AddResourcesPathsPass::class,
            AddImagineClassPass::class,
            DoctrineMigrationsPass::class,
            PickerProviderPass::class,
            RegisterFragmentsPass::class,
            FragmentRendererPass::class,
            MapFragmentsToGlobalsPass::class,
            RegisterHookListenersPass::class,
        ];

        $container = $this->createMock(ContainerBuilder::class);

        $container
            ->expects($this->once())
            ->method('getParameter')
            ->with('kernel.root_dir')
            ->willReturn($this->getFixturesDir().'/app')
        ;

        $container
            ->expects($this->exactly(\count($passes)))
            ->method('addCompilerPass')
            ->with(
                $this->callback(function ($param) use ($passes) {
                    return \in_array(\get_class($param), $passes, true);
                })
            )
        ;

        $bundle = new ContaoCoreBundle();
        $bundle->build($container);
    }

    public function testAddsPackagesPassBeforeAssetsPackagesPass(): void
    {
        $container = new ContainerBuilder();
        $container->setParameter('kernel.root_dir', $this->getFixturesDir().'/app');

        $bundle = new ContaoCoreBundle();
        $bundle->build($container);

        $classes = [];

        foreach ($container->getCompilerPassConfig()->getPasses() as $pass) {
            $reflection = new \ReflectionClass($pass);
            $classes[] = $reflection->getName();
        }

        $packagesPosition = array_search(AddPackagesPass::class, $classes, true);
        $assetsPosition = array_search(AddAssetsPackagesPass::class, $classes, true);

        $this->assertTrue($packagesPosition < $assetsPosition);
    }

    public function testAddsFragmentsPassBeforeHooksPass(): void
    {
        $container = new ContainerBuilder();
        $container->setParameter('kernel.root_dir', $this->getFixturesDir().'/app');

        $bundle = new ContaoCoreBundle();
        $bundle->build($container);

        $classes = [];

        foreach ($container->getCompilerPassConfig()->getPasses() as $pass) {
            $reflection = new \ReflectionClass($pass);
            $classes[] = $reflection->getName();
        }

        $fragmentsPosition = array_search(RegisterFragmentsPass::class, $classes, true);
        $hookPosition = array_search(RegisterHookListenersPass::class, $classes, true);

        $this->assertTrue($fragmentsPosition < $hookPosition);
    }
}
