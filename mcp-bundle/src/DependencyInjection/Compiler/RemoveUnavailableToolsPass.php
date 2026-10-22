<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\McpBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

final class RemoveUnavailableToolsPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (!$container->getParameter('contao.template_studio.enabled')) {
            $container->removeDefinition('contao_mcp.tool.template_snapshot_tools');
            $container->removeDefinition('contao_mcp.tool.twig_environment_tools');
            $container->removeDefinition('contao_mcp.tool.user_template_tools');
        }

        if (!$container->has('contao.search.backend')) {
            $container->removeDefinition('contao_mcp.tool.backend_search_tools');
        }
    }
}
