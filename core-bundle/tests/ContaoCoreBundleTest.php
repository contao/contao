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
use Contao\CoreBundle\DependencyInjection\Compiler\AddAvailableTransportsPass;
use Contao\CoreBundle\DependencyInjection\Compiler\AddCronJobsPass;
use Contao\CoreBundle\DependencyInjection\Compiler\AddNativeTransportFactoryPass;
use Contao\CoreBundle\DependencyInjection\Compiler\AddPackagesPass;
use Contao\CoreBundle\DependencyInjection\Compiler\AddResourcesPathsPass;
use Contao\CoreBundle\DependencyInjection\Compiler\AddSessionBagsPass;
use Contao\CoreBundle\DependencyInjection\Compiler\CrawlerPass;
use Contao\CoreBundle\DependencyInjection\Compiler\DataContainerCallbackPass;
use Contao\CoreBundle\DependencyInjection\Compiler\MakeServicesPublicPass;
use Contao\CoreBundle\DependencyInjection\Compiler\PickerProviderPass;
use Contao\CoreBundle\DependencyInjection\Compiler\RegisterFragmentsPass;
use Contao\CoreBundle\DependencyInjection\Compiler\RegisterHookListenersPass;
use Contao\CoreBundle\DependencyInjection\Compiler\RegisterPagesPass;
use Contao\CoreBundle\DependencyInjection\Compiler\RemembermeServicesPass;
use Contao\CoreBundle\DependencyInjection\Compiler\RewireTwigPathsPass;
use Contao\CoreBundle\DependencyInjection\Compiler\SearchIndexerPass;
use Contao\CoreBundle\DependencyInjection\Compiler\TaggedMigrationsPass;
use Contao\CoreBundle\DependencyInjection\Compiler\TranslationDataCollectorPass;
use Contao\CoreBundle\DependencyInjection\Security\ContaoLoginFactory;
use Contao\CoreBundle\Event\ContaoCoreEvents;
use Contao\CoreBundle\Event\GenerateSymlinksEvent;
use Contao\CoreBundle\Event\MenuEvent;
use Contao\CoreBundle\Event\PreviewUrlConvertEvent;
use Contao\CoreBundle\Event\PreviewUrlCreateEvent;
use Contao\CoreBundle\Event\RobotsTxtEvent;
use Contao\CoreBundle\Event\SlugValidCharactersEvent;
use Symfony\Bundle\SecurityBundle\DependencyInjection\SecurityExtension;
use Symfony\Cmf\Component\Routing\DependencyInjection\Compiler\RegisterRouteEnhancersPass;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\EventDispatcher\DependencyInjection\AddEventAliasesPass;
use Symfony\Component\HttpKernel\DependencyInjection\FragmentRendererPass;

class ContaoCoreBundleTest extends TestCase
{
    public function testAddsTheCompilerPasses(): void
    {
        $passes = [
            AddEventAliasesPass::class,
            MakeServicesPublicPass::class,
            AddPackagesPass::class,
            AddAssetsPackagesPass::class,
            AddSessionBagsPass::class,
            AddResourcesPathsPass::class,
            TaggedMigrationsPass::class,
            PickerProviderPass::class,
            RegisterPagesPass::class,
            RegisterFragmentsPass::class,
            RegisterFragmentsPass::class,
            RegisterFragmentsPass::class,
            RegisterFragmentsPass::class,
            FragmentRendererPass::class,
            RemembermeServicesPass::class,
            DataContainerCallbackPass::class,
            TranslationDataCollectorPass::class,
            RegisterHookListenersPass::class,
            SearchIndexerPass::class,
            CrawlerPass::class,
            AddCronJobsPass::class,
            AddAvailableTransportsPass::class,
            RegisterRouteEnhancersPass::class,
            RewireTwigPathsPass::class,
            AddNativeTransportFactoryPass::class,
        ];

        $security = $this->createMock(SecurityExtension::class);
        $security
            ->expects($this->once())
            ->method('addSecurityListenerFactory')
            ->with($this->callback(
                static function ($param) {
                    return $param instanceof ContaoLoginFactory;
                }
            ))
        ;

        $container = $this->createMock(ContainerBuilder::class);
        $container
            ->expects($this->exactly(\count($passes)))
            ->method('addCompilerPass')
            ->with($this->callback(
                function (CompilerPassInterface $pass) use ($passes): bool {
                    if ($pass instanceof AddEventAliasesPass) {
                        $eventAliases = [
                            GenerateSymlinksEvent::class => ContaoCoreEvents::GENERATE_SYMLINKS,
                            MenuEvent::class => ContaoCoreEvents::BACKEND_MENU_BUILD,
                            PreviewUrlCreateEvent::class => ContaoCoreEvents::PREVIEW_URL_CREATE,
                            PreviewUrlConvertEvent::class => ContaoCoreEvents::PREVIEW_URL_CONVERT,
                            RobotsTxtEvent::class => ContaoCoreEvents::ROBOTS_TXT,
                            SlugValidCharactersEvent::class => ContaoCoreEvents::SLUG_VALID_CHARACTERS,
                        ];

                        $this->assertEquals(new AddEventAliasesPass($eventAliases), $pass);
                    }

                    $this->assertContains(\get_class($pass), $passes);

                    return true;
                }
            ))
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
