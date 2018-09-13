<?php

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle;

use Contao\CoreBundle\DependencyInjection\Compiler\AddImagineClassPass;
use Contao\CoreBundle\DependencyInjection\Compiler\AddPackagesPass;
use Contao\CoreBundle\DependencyInjection\Compiler\AddResourcesPathsPass;
use Contao\CoreBundle\DependencyInjection\Compiler\AddSessionBagsPass;
use Contao\CoreBundle\DependencyInjection\Compiler\DoctrineMigrationsPass;
use Contao\CoreBundle\DependencyInjection\Compiler\PickerProviderPass;
use Contao\CoreBundle\DependencyInjection\ContaoCoreExtension;
use Symfony\Component\Console\Application;
use Symfony\Component\DependencyInjection\Compiler\PassConfig;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use TYPO3\PharStreamWrapper\Behavior;
use TYPO3\PharStreamWrapper\Interceptor\PharExtensionInterceptor;
use TYPO3\PharStreamWrapper\Manager;
use TYPO3\PharStreamWrapper\PharStreamWrapper;

/**
 * Configures the Contao core bundle.
 *
 * @author Leo Feyer <https://github.com/leofeyer>
 * @author Andreas Schempp <https://github.com/aschempp>
 */
class ContaoCoreBundle extends Bundle
{
    const SCOPE_BACKEND = 'backend';
    const SCOPE_FRONTEND = 'frontend';

    /**
     * {@inheritdoc}
     */
    public function getContainerExtension()
    {
        return new ContaoCoreExtension();
    }

    /**
     * {@inheritdoc}
     */
    public function registerCommands(Application $application)
    {
        // disable automatic command registration
    }

    /**
     * {@inheritdoc}
     */
    public function boot()
    {
        parent::boot();

        if (!\in_array('phar', stream_get_wrappers(), true)) {
            return;
        }

        // Destroy and re-initialize the Phar stream wrapper
        Manager::destroy();
        Manager::initialize((new Behavior())->withAssertion(new PharExtensionInterceptor()));
        stream_wrapper_unregister('phar');
        stream_wrapper_register('phar', PharStreamWrapper::class);
    }

    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $container->addCompilerPass(
            new AddPackagesPass($container->getParameter('kernel.root_dir').'/../vendor/composer/installed.json')
        );

        $container->addCompilerPass(new AddSessionBagsPass());
        $container->addCompilerPass(new AddResourcesPathsPass());
        $container->addCompilerPass(new AddImagineClassPass());
        $container->addCompilerPass(new DoctrineMigrationsPass(), PassConfig::TYPE_BEFORE_REMOVING);
        $container->addCompilerPass(new PickerProviderPass());
    }
}
