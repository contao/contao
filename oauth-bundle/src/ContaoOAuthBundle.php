<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\OAuthBundle;

use Symfony\Component\Config\Definition\Configurator\DefinitionConfigurator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;

class ContaoOAuthBundle extends AbstractBundle
{
    protected string $extensionAlias = 'contao_oauth';

    public function configure(DefinitionConfigurator $definition): void
    {
        $definition
            ->rootNode()
            ->children()
                ->arrayNode('resource')
                    ->info('The protected resource the issued access tokens are bound to.')
                    ->isRequired()
                    ->children()
                        ->scalarNode('route')
                            ->info('The name of the route that serves the protected resource.')
                            ->isRequired()
                            ->cannotBeEmpty()
                        ->end()
                        ->scalarNode('name')
                            ->defaultValue('Contao')
                        ->end()
                        ->arrayNode('scopes')
                            ->scalarPrototype()->end()
                            ->requiresAtLeastOneElement()
                            ->isRequired()
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('cimd_trusted_domains')
                    ->info('Hosts allowed to serve Client ID Metadata Documents.')
                    ->scalarPrototype()->end()
                    ->defaultValue([])
                ->end()
            ->end()
        ;
    }

    public function loadExtension(array $config, ContainerConfigurator $configurator, ContainerBuilder $container): void
    {
        $configurator->import('../config/services.yaml');

        $container->setParameter('contao_oauth.resource.route', $config['resource']['route']);
        $container->setParameter('contao_oauth.resource.name', $config['resource']['name']);
        $container->setParameter('contao_oauth.resource.scopes', $config['resource']['scopes']);
        $container->setParameter('contao_oauth.cimd_trusted_domains', $config['cimd_trusted_domains']);
    }
}
