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
     * @return array<string,array<int,string[]>>
     */
    private function getHooks(ContainerBuilder $container): array
    {
        $hooks = [];
        $serviceIds = $container->findTaggedServiceIds('contao.hook');

        foreach ($serviceIds as $serviceId => $tags) {
            foreach ($tags as $attributes) {
                $this->addHookCallback($hooks, $serviceId, $attributes);
            }
        }

        return $hooks;
    }

    /**
     * @throws InvalidConfigurationException
     */
    private function addHookCallback(array &$hooks, string $serviceId, array $attributes): void
    {
        if (!isset($attributes['hook'])) {
            throw new InvalidConfigurationException(
                sprintf('Missing hook attribute in tagged hook service with service id "%s"', $serviceId)
            );
        }

        $priority = (int) ($attributes['priority'] ?? 0);

        $hooks[$attributes['hook']][$priority][] = [$serviceId, $this->getMethod($attributes)];
    }

    private function getMethod(array $attributes): string
    {
        if (isset($attributes['method'])) {
            return (string) $attributes['method'];
        }

        return 'on'.ucfirst($attributes['hook']);
    }
}
