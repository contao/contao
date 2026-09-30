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

use Contao\McpBundle\Tool\BackendSearchTools;
use Contao\McpBundle\Tool\TemplateSnapshotTools;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

final class RemoveUnavailableToolsPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (!$container->has('contao.twig.studio.template_snapshots')) {
            $container->removeDefinition(TemplateSnapshotTools::class);
        }

        if (!$container->has('contao.search.backend')) {
            $container->removeDefinition(BackendSearchTools::class);
        }
    }
}
