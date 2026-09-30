<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\McpBundle;

use Symfony\Component\Config\Definition\Configurator\DefinitionConfigurator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;

class ContaoMcpBundle extends AbstractBundle
{
    public function configure(DefinitionConfigurator $definition): void
    {
        $definition->rootNode()
            ->children()
                ->integerNode('max_binary_payload_size')
                    ->min(1)
                    ->defaultNull()
                ->end()
            ->end()
        ;
    }

    public function loadExtension(array $config, ContainerConfigurator $configurator, ContainerBuilder $container): void
    {
        $configurator->import('../config/services.yaml');
        $configurator->parameters()->set('contao.mcp.max_binary_payload_size', $config['max_binary_payload_size']);

        if ($container->has('contao.twig.studio.template_snapshots')) {
            $configurator->import('../config/template_snapshots.yaml');
        }

        if ($container->has('contao.search.backend')) {
            $configurator->import('../config/backend_search.yaml');
        }
    }
}
