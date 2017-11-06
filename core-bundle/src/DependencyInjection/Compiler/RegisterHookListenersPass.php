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

class RegisterHookListenersPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container): void
    {
        if (!$container->hasDefinition('contao.framework')) {
            return;
        }

        $hooks = $this->getHooks($container);

        if (empty($hooks)) {
            return;
        }

        // Sort the listeners by priority
        foreach (array_keys($hooks) as $hook) {
            krsort($hooks[$hook]);
        }

        $definition = $container->getDefinition('contao.framework');
        $definition->addMethodCall('setHookListeners', [$hooks]);
    }

    /**
     * Returns the hook listeners.
     *
     * @param ContainerBuilder $container
     *
     * @return array
     */
    private function getHooks(ContainerBuilder $container): array
    {
        $hooks = [];
        $serviceIds = $container->findTaggedServiceIds('contao.hook');

        foreach ($serviceIds as $serviceId => $tags) {
            foreach ($tags as $attributes) {
                $this->checkRequiredAttributes($serviceId, $attributes);

                $priority = (int) ($attributes['priority'] ?? 0);
                $hook = $attributes['hook'];

                $hooks[$hook][$priority][] = [$serviceId, $attributes['method']];
            }
        }

        return $hooks;
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
