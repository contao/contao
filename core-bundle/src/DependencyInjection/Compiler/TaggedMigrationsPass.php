<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class TaggedMigrationsPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (!$container->has('contao.migration.collection')) {
            return;
        }

        $definition = $container->findDefinition('contao.migration.collection');
        $services = [];

        foreach ($container->findTaggedServiceIds('contao.migration', true) as $serviceId => $attributes) {
            $priority = $attributes[0]['priority'] ?? 0;
            $class = $container->getDefinition($serviceId)->getClass();
            $services[$priority][$class] = new Reference($serviceId);
        }

        foreach (array_keys($services) as $priority) {
            ksort($services[$priority], SORT_NATURAL); // Order by class name ascending
        }

        if ($services) {
            krsort($services); // Order by priority descending
            $services = array_merge(...$services);
        }

        $definition->setArgument(0, $services);
    }
}
