<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\ApiBundle;

use Symfony\Component\Config\Definition\Configurator\DefinitionConfigurator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;

class ContaoApiBundle extends AbstractBundle
{
    public function configure(DefinitionConfigurator $definition): void
    {
        $definition->rootNode()
            ->children()
            ->scalarNode('api_prefix')
            ->defaultValue('/_api')
            ->info('The general route prefix at which Contao shall expose the API.')
            ->end()
            ->scalarNode('data_container_api_prefix')
            ->defaultValue('/backend/dc')
            ->info('The DC specific subprefix at which Contao shall expose the API.')
            ->end()
            ->end()
        ;
    }

    public function loadExtension(array $config, ContainerConfigurator $configurator, ContainerBuilder $container): void
    {
        $configurator->parameters()
            ->set('contao_api.api_prefix', $config['api_prefix'])
            ->set('contao_api.data_container_api_prefix', $config['data_container_api_prefix'])
        ;
    }
}
