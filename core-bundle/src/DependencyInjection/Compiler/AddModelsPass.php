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

use Symfony\Component\Config\Definition\Exception\InvalidDefinitionException;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class AddModelsPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (!$container->has('contao.model')) {
            return;
        }

        if (!$models = $this->getModels($container)) {
            return;
        }

        $definition = $container->findDefinition('contao.model');
        $definition->addMethodCall('addModels', [$models]);
    }

    /**
     * @return array<string, array<int, array<string>>>
     */
    private function getModels(ContainerBuilder $container): array
    {
        $models = [];
        $serviceIds = $container->findTaggedServiceIds('contao.model');

        foreach ($serviceIds as $serviceId => $tags) {
            foreach ($tags as $attributes) {
                if (!isset($attributes['table'])) {
                    throw new InvalidDefinitionException(\sprintf('Missing table attribute in tagged model service with service id "%s"', $serviceId));
                }

                $models[$attributes['table']] = $serviceId;
            }
        }

        return $models;
    }
}
