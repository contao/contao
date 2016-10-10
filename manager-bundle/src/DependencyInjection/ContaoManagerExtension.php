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
use Contao\ManagerBundle\ContaoManager\PluginLoader;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

/**
 * Prepends plugin configs and the bundle services to the container.
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
        if (!$container->has('contao_manager.plugin_loader')) {
            return;
        }

        /** @var ConfigPluginInterface[] $plugins */
        $plugins = $container->get('contao_manager.plugin_loader')->getInstancesOf(PluginLoader::CONFIG_PLUGINS);
        $config = $this->getConfig();

        foreach ($plugins as $plugin) {
            $plugin->prependConfig($config, $container);
        }
    }

    /**
     * @inheritdoc
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $loader = new YamlFileLoader(
            $container,
            new FileLocator(__DIR__.'/../Resources/config')
        );

        $loader->load('services.yml');
    }

    /**
     * Gets the configuration from Contao Manager.
     *
     * @return array
     */
    private function getConfig()
    {
        // TODO: We do not have a managed config yet, so we'll just pass an empty array
        return [];
    }
}
