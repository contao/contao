<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle;

use Composer\InstalledVersions;
use Contao\CoreBundle\DependencyInjection\Compiler\AddAssetsPackagesPass;
use Contao\CoreBundle\DependencyInjection\Compiler\AddAvailableTransportsPass;
use Contao\CoreBundle\DependencyInjection\Compiler\AddCronJobsPass;
use Contao\CoreBundle\DependencyInjection\Compiler\AddNativeTransportFactoryPass;
use Contao\CoreBundle\DependencyInjection\Compiler\AddResourcesPathsPass;
use Contao\CoreBundle\DependencyInjection\Compiler\AddSessionBagsPass;
use Contao\CoreBundle\DependencyInjection\Compiler\ConfigureFilesystemPass;
use Contao\CoreBundle\DependencyInjection\Compiler\CrawlerPass;
use Contao\CoreBundle\DependencyInjection\Compiler\DataContainerCallbackPass;
use Contao\CoreBundle\DependencyInjection\Compiler\IntlInstalledLocalesAndCountriesPass;
use Contao\CoreBundle\DependencyInjection\Compiler\LoggerChannelPass;
use Contao\CoreBundle\DependencyInjection\Compiler\MakeServicesPublicPass;
use Contao\CoreBundle\DependencyInjection\Compiler\PickerProviderPass;
use Contao\CoreBundle\DependencyInjection\Compiler\RegisterFragmentsPass;
use Contao\CoreBundle\DependencyInjection\Compiler\RegisterHookListenersPass;
use Contao\CoreBundle\DependencyInjection\Compiler\RegisterPagesPass;
use Contao\CoreBundle\DependencyInjection\Compiler\RewireTwigPathsPass;
use Contao\CoreBundle\DependencyInjection\Compiler\SearchIndexerPass;
use Contao\CoreBundle\DependencyInjection\Compiler\TaggedMigrationsPass;
use Contao\CoreBundle\DependencyInjection\Compiler\TranslationDataCollectorPass;
use Contao\CoreBundle\DependencyInjection\ContaoCoreExtension;
use Contao\CoreBundle\DependencyInjection\Security\ContaoLoginFactory;
use Contao\CoreBundle\Event\ContaoCoreEvents;
use Contao\CoreBundle\Event\GenerateSymlinksEvent;
use Contao\CoreBundle\Event\MenuEvent;
use Contao\CoreBundle\Event\PreviewUrlConvertEvent;
use Contao\CoreBundle\Event\PreviewUrlCreateEvent;
use Contao\CoreBundle\Event\RobotsTxtEvent;
use Contao\CoreBundle\Event\SitemapEvent;
use Contao\CoreBundle\Event\SlugValidCharactersEvent;
use Contao\CoreBundle\Fragment\Reference\ContentElementReference;
use Contao\CoreBundle\Fragment\Reference\FrontendModuleReference;
use Symfony\Bundle\SecurityBundle\DependencyInjection\SecurityExtension;
use Symfony\Cmf\Component\Routing\DependencyInjection\Compiler\RegisterRouteEnhancersPass;
use Symfony\Component\DependencyInjection\Compiler\PassConfig;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\EventDispatcher\DependencyInjection\AddEventAliasesPass;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class ContaoCoreBundle extends Bundle
{
    final public const SCOPE_BACKEND = 'backend';
    final public const SCOPE_FRONTEND = 'frontend';

    public function getContainerExtension(): ContaoCoreExtension
    {
        return new ContaoCoreExtension();
    }

    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        /** @var SecurityExtension $extension */
        $extension = $container->getExtension('security');
        $extension->addAuthenticatorFactory(new ContaoLoginFactory());

        $container->addCompilerPass(
            new AddEventAliasesPass([
                GenerateSymlinksEvent::class => ContaoCoreEvents::GENERATE_SYMLINKS,
                MenuEvent::class => ContaoCoreEvents::BACKEND_MENU_BUILD,
                PreviewUrlCreateEvent::class => ContaoCoreEvents::PREVIEW_URL_CREATE,
                PreviewUrlConvertEvent::class => ContaoCoreEvents::PREVIEW_URL_CONVERT,
                RobotsTxtEvent::class => ContaoCoreEvents::ROBOTS_TXT,
                SitemapEvent::class => ContaoCoreEvents::SITEMAP,
                SlugValidCharactersEvent::class => ContaoCoreEvents::SLUG_VALID_CHARACTERS,
            ])
        );

        $container->addCompilerPass(new MakeServicesPublicPass());
        $container->addCompilerPass(new AddAssetsPackagesPass());
        $container->addCompilerPass(new AddSessionBagsPass());
        $container->addCompilerPass(new AddResourcesPathsPass());
        $container->addCompilerPass(new TaggedMigrationsPass());
        $container->addCompilerPass(new PickerProviderPass());
        $container->addCompilerPass(new RegisterPagesPass(), PassConfig::TYPE_BEFORE_OPTIMIZATION, 1);

        $container->addCompilerPass(
            new RegisterFragmentsPass(
                FrontendModuleReference::TAG_NAME,
                FrontendModuleReference::GLOBALS_KEY,
                FrontendModuleReference::PROXY_CLASS,
                'contao.listener.module_template_options'
            )
        );

        $container->addCompilerPass(
            new RegisterFragmentsPass(
                ContentElementReference::TAG_NAME,
                ContentElementReference::GLOBALS_KEY,
                ContentElementReference::PROXY_CLASS,
                'contao.listener.element_template_options'
            )
        );

        $container->addCompilerPass(new DataContainerCallbackPass());
        $container->addCompilerPass(new TranslationDataCollectorPass());
        $container->addCompilerPass(new RegisterHookListenersPass(), PassConfig::TYPE_OPTIMIZE);
        $container->addCompilerPass(new SearchIndexerPass()); // Must be before the CrawlerPass
        $container->addCompilerPass(new CrawlerPass());
        $container->addCompilerPass(new AddCronJobsPass());
        $container->addCompilerPass(new AddAvailableTransportsPass());
        $container->addCompilerPass(new RegisterRouteEnhancersPass('contao.routing.page_router', 'contao.page_router_enhancer'));
        $container->addCompilerPass(new RewireTwigPathsPass());
        $container->addCompilerPass(new AddNativeTransportFactoryPass());
        $container->addCompilerPass(new IntlInstalledLocalesAndCountriesPass());
        $container->addCompilerPass(new LoggerChannelPass(), PassConfig::TYPE_BEFORE_OPTIMIZATION, -1);
        $container->addCompilerPass(new ConfigureFilesystemPass());
    }

    public static function getVersion(): string
    {
        try {
            $version = (string) InstalledVersions::getPrettyVersion('contao/core-bundle');
        } catch (\OutOfBoundsException) {
            $version = '';
        }

        if ('' === $version) {
            $version = (string) InstalledVersions::getPrettyVersion('contao/contao');
        }

        return $version;
    }
}
