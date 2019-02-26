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
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class DataContainerCallbackPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container): void
    {
        if (!$container->hasDefinition('contao.listener.data_container_callback')) {
            return;
        }

        $callbacks = $this->getCallbacks($container);

        if (empty($callbacks)) {
            return;
        }

        $definition = $container->getDefinition('contao.listener.data_container_callback');
        $definition->addMethodCall('setCallbacks', [$callbacks]);
    }

    /**
     * @return array<string,array<int,string[]>>
     */
    private function getCallbacks(ContainerBuilder $container): array
    {
        $callbacks = [];
        $serviceIds = $container->findTaggedServiceIds('contao.callback');

        foreach ($serviceIds as $serviceId => $tags) {
            if ($container->hasAlias($serviceId)) {
                $serviceId = (string) $container->getAlias($serviceId);
            }

            foreach ($tags as $attributes) {
                $this->addCallback($callbacks, $serviceId, $attributes);
            }

            $container->findDefinition($serviceId)->setPublic(true);
        }

        return $callbacks;
    }

    private function addCallback(array &$callbacks, string $serviceId, array $attributes): void
    {
        if (!isset($attributes['table'])) {
            throw new InvalidConfigurationException(
                sprintf('Missing table attribute in tagged callback service ID "%s"', $serviceId)
            );
        }

        if (!isset($attributes['target'])) {
            throw new InvalidConfigurationException(
                sprintf('Missing target attribute in tagged callback service ID "%s"', $serviceId)
            );
        }

        if (
            '_callback' !== substr($attributes['target'], -9)
            && false === strpos($attributes['target'], '.panel_callback.')
            && !\in_array(substr($attributes['target'], -7), ['.wizard', '.xlabel'], true)
        ) {
            $attributes['target'] .= '_callback';
        }

        $priority = (int) ($attributes['priority'] ?? 0);

        $callbacks[$attributes['table']][$attributes['target']][$priority][] = [
            $serviceId,
            $this->getMethod($attributes),
        ];
    }

    private function getMethod(array $attributes): string
    {
        if (isset($attributes['method'])) {
            return (string) $attributes['method'];
        }

        $keys = explode('.', $attributes['target']);
        $callback = end($keys);

        if (0 === strncmp($callback, 'on', 2)) {
            $callback = substr($callback, 2);
        }

        return 'on'.Container::camelize($callback);
    }
}
