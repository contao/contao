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
                ->scalarNode('backend_path')
                    ->defaultValue('/_mcp/backend')
                    ->info('The HTTP route at which Contao exposes the backend MCP server.')
                ->end()
            ->end()
        ;
    }

    public function prependExtension(ContainerConfigurator $configurator, ContainerBuilder $container): void
    {
        if ($container->hasExtension('security')) {
            // Match the route so custom paths remain protected before public fallback rules
            $container->prependExtensionConfig('security', [
                'access_control' => [
                    ['route' => 'contao_mcp_backend', 'roles' => ['ROLE_USER']],
                ],
            ]);
        }
    }

    public function loadExtension(array $config, ContainerConfigurator $configurator, ContainerBuilder $container): void
    {
        $configurator->import('../config/services.yaml');

        $configurator->parameters()
            ->set('contao_mcp.backend_path', $config['backend_path'])
        ;
    }
}
