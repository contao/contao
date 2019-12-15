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
use Contao\CoreBundle\DependencyInjection\ContaoCoreExtension;
use Contao\CoreBundle\DependencyInjection\Security\ContaoLoginFactory;
use Contao\CoreBundle\Fragment\Reference\ContentElementReference;
use Contao\CoreBundle\Fragment\Reference\FrontendModuleReference;
use Symfony\Bundle\SecurityBundle\DependencyInjection\SecurityExtension;
use Symfony\Component\Console\Application;
use Symfony\Component\DependencyInjection\Compiler\PassConfig;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\HttpKernel\DependencyInjection\FragmentRendererPass;

class ContaoCoreBundle extends Bundle
{
    public const SCOPE_BACKEND = 'backend';
    public const SCOPE_FRONTEND = 'frontend';

    /**
     * {@inheritdoc}
     */
    public function getContainerExtension(): ContaoCoreExtension
    {
        return new ContaoCoreExtension();
    }

    /**
     * {@inheritdoc}
     */
    public function registerCommands(Application $application): void
    {
        // disable automatic command registration
    }

    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        /** @var SecurityExtension $extension */
        $extension = $container->getExtension('security');
        $extension->addSecurityListenerFactory(new ContaoLoginFactory());

        $container->addCompilerPass(new MakeServicesPublicPass());
        $container->addCompilerPass(new AddPackagesPass());
        $container->addCompilerPass(new AddAssetsPackagesPass());
        $container->addCompilerPass(new AddSessionBagsPass());
        $container->addCompilerPass(new AddResourcesPathsPass());
        $container->addCompilerPass(new PickerProviderPass());
        $container->addCompilerPass(new RegisterFragmentsPass(FrontendModuleReference::TAG_NAME));
        $container->addCompilerPass(new RegisterFragmentsPass(ContentElementReference::TAG_NAME));
        $container->addCompilerPass(new FragmentRendererPass('contao.fragment.handler'));
        $container->addCompilerPass(new RemembermeServicesPass('contao_frontend'));
        $container->addCompilerPass(new MapFragmentsToGlobalsPass());
        $container->addCompilerPass(new DataContainerCallbackPass());
        $container->addCompilerPass(new TranslationDataCollectorPass());
        $container->addCompilerPass(new RegisterHookListenersPass(), PassConfig::TYPE_OPTIMIZE);
        $container->addCompilerPass(new SearchIndexerPass());
        $container->addCompilerPass(new EscargotSubscriberPass());
        $container->addCompilerPass(new AddCronJobsPass());
    }
}
