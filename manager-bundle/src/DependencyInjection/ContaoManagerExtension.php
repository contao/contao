<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2016 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\ManagerBundle\DependencyInjection;

use Contao\ManagerBundle\ContaoManager\Config\ConfigPluginInterface;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

/**
 * Adds the bundle services to the container.
 *
 * @author Andreas Schempp <https://github.com/aschempp>
 */
class ContaoManagerExtension extends Extension implements PrependExtensionInterface
{
    /**
     * @inheritdoc
     */
    public function prepend(ContainerBuilder $container)
    {
        $loader = new YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));

        $loader->load('framework.yml');
        $loader->load('twig.yml');
        $loader->load('doctrine.yml');
        $loader->load('swiftmailer.yml');
        $loader->load('monolog.yml');

        if (in_array('Lexik\\Bundle\\MaintenanceBundle\\LexikMaintenanceBundle', $container->getParameter('kernel.bundles'), true)) {
            $loader->load('lexik_maintenance.yml');
        }

        if ('dev' === $container->getParameter('kernel.environment')) {
            $loader->load('web_profiler.yml');
        }

        $coreLoader = new YamlFileLoader($container, new FileLocator(__DIR__.'/../../../core-bundle/src/Resources/config'));
        $coreLoader->load('security.yml');

        $this->prependPlugins($container);
    }

    public function load(array $configs, ContainerBuilder $container)
    {
        $loader = new YamlFileLoader(
            $container,
            new FileLocator(__DIR__.'/../Resources/config')
        );

        $loader->load('services.yml');
    }

    private function prependPlugins(ContainerBuilder $container)
    {
        if (!$container->has('contao_manager.plugin_loader')) {
            return;
        }

        foreach ($container->get('contao_manager.plugin_loader')->getInstances() as $plugin) {
            if ($plugin instanceof ConfigPluginInterface) {
                // We do not have a managed config yet, so we'll just pass an empty array
                $plugin->prependConfig([], $container);
            }
        }
    }
}
