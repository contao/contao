<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2017 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\DependencyInjection\Compiler;

use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class RegisterHooksPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container): void
    {
        if (!$container->hasDefinition('contao.framework')) {
            return;
        }

        $serviceIds = $container->findTaggedServiceIds('contao.hook');
        $hooks = [];

        foreach ($serviceIds as $serviceId => $tags) {
            foreach ($tags as $attributes) {
                $this->checkRequiredAttributes($serviceId, $attributes);

                $priority = (int) ($attributes['priority'] ?? 0);
                $hook = $attributes['hook'];

                $hooks[$hook][$priority][] = [$serviceId, $attributes['method']];
            }
        }

        if (\count($hooks) > 0) {
            foreach (array_keys($hooks) as $hook) {
                krsort($hooks[$hook]); // order by priority
            }

            $definition = $container->getDefinition('contao.framework');
            $definition->setArgument(6, $hooks);
        }
    }

    /**
     * Checks that required attributes (hook and method) are set.
     *
     * @param string $serviceId
     * @param array  $attributes
     *
     * @throws InvalidConfigurationException
     */
    private function checkRequiredAttributes(string $serviceId, array $attributes): void
    {
        if (!isset($attributes['hook'])) {
            throw new InvalidConfigurationException(
                sprintf('Missing hook attribute in tagged hook service with service id "%s"', $serviceId)
            );
        }

        if (!isset($attributes['method'])) {
            throw new InvalidConfigurationException(
                sprintf('Missing method attribute in tagged hook service with service id "%s"', $serviceId)
            );
        }
    }
}
