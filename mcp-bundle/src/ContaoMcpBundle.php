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

use Contao\McpBundle\DependencyInjection\Compiler\RemoveUnavailableToolsPass;
use Contao\McpBundle\Routing\McpRequestMatcher;
use Symfony\Component\DependencyInjection\Compiler\PassConfig;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;

class ContaoMcpBundle extends AbstractBundle
{
    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        $container->addCompilerPass(new RemoveUnavailableToolsPass(), PassConfig::TYPE_BEFORE_OPTIMIZATION, 20);
    }

    public function prependExtension(ContainerConfigurator $configurator, ContainerBuilder $container): void
    {
        $container->prependExtensionConfig('contao_oauth_server', [
            'resource' => [
                'route' => McpRequestMatcher::ROUTE,
                'name' => 'Contao MCP',
                'scopes' => ['mcp'],
            ],
            'cimd_trusted_domains' => [
                'chatgpt.com',
                'claude.ai',
                'vscode.dev',
            ],
        ]);
    }

    public function loadExtension(array $config, ContainerConfigurator $configurator, ContainerBuilder $container): void
    {
        $configurator->import('../config/services.yaml');
        $configurator->import('../config/template_snapshots.yaml');
        $configurator->import('../config/backend_search.yaml');
    }
}
