<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests;

use Contao\CoreBundle\ContaoCoreBundle;
use Contao\CoreBundle\DependencyInjection\Compiler\AddAssetsPackagesPass;
use Contao\CoreBundle\DependencyInjection\Compiler\AddCronJobsPass;
use Contao\CoreBundle\DependencyInjection\Compiler\AddPackagesPass;
use Contao\CoreBundle\DependencyInjection\Compiler\AddResourcesPathsPass;
use Contao\CoreBundle\DependencyInjection\Compiler\AddSessionBagsPass;
use Contao\CoreBundle\DependencyInjection\Compiler\DataContainerCallbackPass;
use Contao\CoreBundle\DependencyInjection\Compiler\EscargotSubscriberPass;
use Contao\CoreBundle\DependencyInjection\Compiler\MakeServicesPublicPass;
use Contao\CoreBundle\DependencyInjection\Compiler\MapFragmentsToGlobalsPass;
use Contao\CoreBundle\DependencyInjection\Compiler\PickerProviderPass;
use Contao\CoreBundle\DependencyInjection\Compiler\RegisterFragmentsPass;
use Contao\CoreBundle\DependencyInjection\Compiler\RegisterHookListenersPass;
use Contao\CoreBundle\DependencyInjection\Compiler\RemembermeServicesPass;
use Contao\CoreBundle\DependencyInjection\Compiler\SearchIndexerPass;
use Contao\CoreBundle\DependencyInjection\Compiler\TranslationDataCollectorPass;
use Contao\CoreBundle\DependencyInjection\Security\ContaoLoginFactory;
use Symfony\Bundle\SecurityBundle\DependencyInjection\SecurityExtension;
use Symfony\Component\Console\Application;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\DependencyInjection\FragmentRendererPass;

class ContaoCoreBundleTest extends TestCase
{
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
            MakeServicesPublicPass::class,
            AddPackagesPass::class,
            AddAssetsPackagesPass::class,
            AddSessionBagsPass::class,
            AddResourcesPathsPass::class,
            PickerProviderPass::class,
            RegisterFragmentsPass::class,
            RegisterFragmentsPass::class,
            FragmentRendererPass::class,
            RemembermeServicesPass::class,
            MapFragmentsToGlobalsPass::class,
            DataContainerCallbackPass::class,
            TranslationDataCollectorPass::class,
            RegisterHookListenersPass::class,
            SearchIndexerPass::class,
            EscargotSubscriberPass::class,
            AddCronJobsPass::class,
        ];

        $security = $this->createMock(SecurityExtension::class);
        $security
            ->expects($this->once())
            ->method('addSecurityListenerFactory')
            ->with(
                $this->callback(static function ($param) {
                    return $param instanceof ContaoLoginFactory;
                })
            )
        ;

        $container = $this->createMock(ContainerBuilder::class);
        $container
            ->expects($this->exactly(\count($passes)))
            ->method('addCompilerPass')
            ->with(
                $this->callback(static function ($param) use ($passes) {
                    return \in_array(\get_class($param), $passes, true);
                })
            )
        ;

        $container
            ->expects($this->once())
            ->method('getExtension')
            ->with('security')
            ->willReturn($security)
        ;

        $bundle = new ContaoCoreBundle();
        $bundle->build($container);
    }

    public function testAddsPackagesPassBeforeAssetsPackagesPass(): void
    {
        $container = new ContainerBuilder();
        $container->registerExtension(new SecurityExtension());

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
        $container->registerExtension(new SecurityExtension());

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
